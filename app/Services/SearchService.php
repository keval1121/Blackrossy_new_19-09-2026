<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function suggest(string $term): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return ['products' => [], 'categories' => []];
        }

        $products = Product::query()
            ->active()
            ->with('primaryImage')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('tags', fn (Builder $tag) => $tag->where('name', 'like', '%'.$term.'%'));
            })
            ->orderByDesc('sold_count')
            ->limit(config('shop.search_suggestions'))
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'url' => $product->url(),
                'price' => money($product->selling_price),
                'image' => $product->thumbUrl(),
            ]);

        $categories = Category::query()
            ->active()
            ->withCatalogProducts()
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('display_order')
            ->limit(5)
            ->get()
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'url' => $category->url(),
            ]);

        return [
            'products' => $products,
            'categories' => $categories,
        ];
    }
}
