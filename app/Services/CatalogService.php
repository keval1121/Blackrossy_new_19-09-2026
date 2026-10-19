<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariantValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogService
{
    public function listing(Request $request, ?Category $category = null, ?Category $subcategory = null): array
    {
        $query = Product::query()
            ->active()
            ->with(['primaryImage', 'brand', 'category', 'subCategory', 'activeVariants.values.attributeValue']);

        $leaf = $subcategory ?: $category;
        if ($subcategory) {
            $query->where('sub_category_id', $subcategory->id);
        } elseif ($category) {
            $childIds = $category->children()->pluck('id');
            $query->where(function (Builder $builder) use ($category, $childIds) {
                $builder->where('category_id', $category->id);
                if ($childIds->isNotEmpty()) {
                    $builder->orWhereIn('sub_category_id', $childIds);
                }
            });
        }

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhereHas('tags', fn (Builder $tagQuery) => $tagQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($brands = array_filter((array) $request->get('brand'))) {
            $query->whereIn('brand_id', $brands);
        }

        $min = $request->integer('min_price');
        $max = $request->integer('max_price');
        if ($min > 0) {
            $query->where('selling_price', '>=', $min);
        }
        if ($max > 0) {
            $query->where('selling_price', '<=', $max);
        }

        foreach ((array) $request->get('attr', []) as $attributeId => $valueIds) {
            $valueIds = array_filter((array) $valueIds);
            if (! $valueIds) {
                continue;
            }
            $query->whereHas('variants.values', function (Builder $builder) use ($valueIds) {
                $builder->whereIn('attribute_value_id', $valueIds);
            });
        }

        $this->sort($query, (string) $request->get('sort', 'popular'));

        $products = $query->paginate(config('shop.listing_per_page'))->withQueryString();

        return [
            'products' => $products,
            'filters' => $this->filters($leaf, $category),
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    public function related(Product $product): Collection
    {
        $categoryId = (int) $product->category_id;
        $subCategoryId = (int) ($product->sub_category_id ?: 0);

        $prioritySql = $subCategoryId > 0
            ? "CASE WHEN sub_category_id = {$subCategoryId} THEN 0 WHEN category_id = {$categoryId} THEN 1 ELSE 2 END"
            : "CASE WHEN category_id = {$categoryId} THEN 0 ELSE 1 END";

        return Product::query()
            ->active()
            ->with(['primaryImage', 'images', 'brand'])
            ->where('id', '!=', $product->id)
            ->orderByRaw($prioritySql)
            ->latest()
            ->get();
    }

    private function sort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price'),
            'price_desc' => $query->orderByDesc('selling_price'),
            'newest' => $query->latest(),
            'discount' => $query->orderByRaw('(mrp - selling_price) / NULLIF(mrp, 0) desc'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sold_count')->latest(),
        };
    }

    private function filters(?Category $leaf, ?Category $parent): array
    {
        $scope = Product::query()->active();
        if ($leaf && $leaf->parent_id) {
            $scope->where('sub_category_id', $leaf->id);
        } elseif ($parent) {
            $childIds = $parent->children()->pluck('id');
            $scope->where(function (Builder $builder) use ($parent, $childIds) {
                $builder->where('category_id', $parent->id);
                if ($childIds->isNotEmpty()) {
                    $builder->orWhereIn('sub_category_id', $childIds);
                }
            });
        }

        $brandIds = (clone $scope)->whereNotNull('brand_id')->distinct()->pluck('brand_id');
        $valueIds = ProductVariantValue::query()
            ->whereIn('product_variant_id', function ($sub) use ($scope) {
                $sub->select('id')->from('product_variants')->whereIn('product_id', (clone $scope)->select('id'));
            })
            ->pluck('attribute_value_id');

        $attributes = Attribute::query()
            ->where('is_filterable', true)
            ->with(['values' => fn ($query) => $query->whereIn('id', $valueIds)->orderBy('display_order')])
            ->orderBy('display_order')
            ->get()
            ->filter(fn ($attribute) => $attribute->values->isNotEmpty());

        $price = (clone $scope)->selectRaw('MIN(selling_price) as min_price, MAX(selling_price) as max_price')->first();

        return [
            'brands' => Brand::query()->active()->whereIn('id', $brandIds)->orderBy('name')->get(),
            'attributes' => $attributes,
            'min' => (int) floor((float) ($price->min_price ?? 0)),
            'max' => (int) ceil((float) ($price->max_price ?? 0)),
            'children' => $parent?->children()->active()->withCatalogProducts()->orderBy('display_order')->get() ?? collect(),
        ];
    }
}
