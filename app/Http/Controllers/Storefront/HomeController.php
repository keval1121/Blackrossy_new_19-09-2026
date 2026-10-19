<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::keyed();

        $featuredCategoryIds = $sections->get('featured_categories')?->config['category_ids'] ?? [];
        $featuredProductIds = $sections->get('featured_products')?->config['product_ids'] ?? [];
        $trendingIds = $sections->get('trending')?->config['product_ids'] ?? [];

        $featuredParents = Category::query()
            ->active()
            ->parents()
            ->withCatalogProducts()
            ->with(['children' => fn ($query) => $query->active()->withCatalogProducts()->with('parent')->orderBy('display_order')])
            ->when($featuredCategoryIds, fn ($query) => $query->whereIn('id', $featuredCategoryIds),
                fn ($query) => $query->where('show_on_homepage', true))
            ->orderBy('display_order')
            ->limit(8)
            ->get();

        $featuredSubcategories = $featuredParents
            ->flatMap(fn (Category $category) => $category->children)
            ->values();

        $productWith = ['primaryImage', 'images', 'brand'];

        $featuredProducts = $this->featuredCatalog($featuredProductIds, $productWith);
        $newArrivals = Product::query()->active()->with($productWith)->where('is_new_arrival', true)->latest()->limit(8)->get();
        $bestSellers = Product::query()->active()->with($productWith)->where('is_best_seller', true)->latest('sold_count')->limit(8)->get();

        return view('storefront.home.index', [
            'heroBanner' => Banner::query()->live()->first(),
            'heroProducts' => $this->heroProducts($sections, $productWith, $newArrivals, $bestSellers),
            'sections' => $sections,
            'featuredSubcategories' => $featuredSubcategories,
            'featuredProducts' => $featuredProducts,
            'trendingProducts' => $this->products($trendingIds, $productWith, 'sold_count'),
            'bestSellers' => $bestSellers,
            'newArrivals' => $newArrivals,
        ]);
    }

    /**
     * @param  list<int>  $priorityIds
     * @param  list<string>  $with
     */
    private function featuredCatalog(array $priorityIds, array $with): LengthAwarePaginator
    {
        $priorityIds = array_values(array_unique(array_filter(array_map('intval', $priorityIds))));

        $prioritySql = $priorityIds === []
            ? 'CASE WHEN is_featured = 1 THEN 0 ELSE 1 END'
            : 'CASE WHEN id IN ('.implode(',', $priorityIds).') THEN 0 WHEN is_featured = 1 THEN 1 ELSE 2 END';

        return Product::query()
            ->active()
            ->with($with)
            ->orderByRaw($prioritySql)
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->fragment('featured');
    }

    /**
     * @param  list<string>  $with
     * @return Collection<int, Product>
     */
    private function heroProducts(
        Collection $sections,
        array $with,
        Collection $newArrivals,
        Collection $bestSellers,
    ): Collection {
        $heroProductIds = array_slice(
            array_values(array_filter(array_map('intval', $sections->get('hero_products')?->config['product_ids'] ?? []))),
            0,
            4
        );

        $hasCatalogPhoto = fn (Product $product) => $product->primaryImage !== null || $product->images->isNotEmpty();

        if ($heroProductIds !== [] && ($sections->get('hero_products')?->is_enabled !== false)) {
            $heroOrder = array_flip($heroProductIds);

            return Product::query()
                ->active()
                ->with($with)
                ->whereIn('id', $heroProductIds)
                ->get()
                ->sortBy(fn (Product $product) => $heroOrder[$product->id] ?? PHP_INT_MAX)
                ->filter($hasCatalogPhoto)
                ->values();
        }

        $featuredPool = Product::query()
            ->active()
            ->with($with)
            ->where('is_featured', true)
            ->latest()
            ->limit(8)
            ->get();

        $heroPool = $featuredPool
            ->concat($newArrivals)
            ->concat($bestSellers)
            ->unique('id')
            ->filter($hasCatalogPhoto)
            ->values();

        if ($heroPool->count() < 4) {
            $extra = Product::query()
                ->active()
                ->with($with)
                ->where(fn ($query) => $query->whereHas('primaryImage')->orWhereHas('images'))
                ->latest()
                ->limit(16)
                ->get();
            $heroPool = $heroPool->concat($extra)->unique('id')->filter($hasCatalogPhoto)->values();
        }

        $heroProducts = collect();
        $usedCategories = [];
        foreach ($heroPool as $product) {
            $categoryKey = $product->category_id ?: 'none';
            if (isset($usedCategories[$categoryKey])) {
                continue;
            }
            $usedCategories[$categoryKey] = true;
            $heroProducts->push($product);
            if ($heroProducts->count() === 4) {
                break;
            }
        }

        if ($heroProducts->count() < 4) {
            foreach ($heroPool as $product) {
                if ($heroProducts->contains('id', $product->id)) {
                    continue;
                }
                $heroProducts->push($product);
                if ($heroProducts->count() === 4) {
                    break;
                }
            }
        }

        return $heroProducts->values();
    }

    private function products(array $ids, array $with, string $fallback)
    {
        $query = Product::query()->active()->with($with);
        if ($ids) {
            $orderedIds = array_map('intval', $ids);
            $order = array_flip($orderedIds);

            return $query->whereIn('id', $orderedIds)
                ->get()
                ->sortBy(fn (Product $product) => $order[$product->id] ?? PHP_INT_MAX)
                ->values();
        }

        if ($fallback === 'sold_count') {
            return $query->orderByDesc('sold_count')->limit(8)->get();
        }

        return $query->where($fallback, true)->latest()->limit(8)->get();
    }
}
