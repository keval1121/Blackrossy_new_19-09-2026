<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, CatalogService $catalog)
    {
        $data = $catalog->listing($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('storefront.shop.partials.grid', ['products' => $data['products']])->render(),
                'count' => $data['products']->total(),
            ]);
        }

        return view('storefront.shop.index', [
            'category' => null,
            'subcategory' => null,
            'products' => $data['products'],
            'filters' => $data['filters'],
            'heading' => 'Shop All',
        ]);
    }

    public function category(Request $request, CatalogService $catalog, Category $category, ?string $subcategory = null)
    {
        abort_unless($category->is_active && ! $category->parent_id, 404);

        $child = null;
        if ($subcategory) {
            $child = $category->children()->active()->where('slug', $subcategory)->firstOrFail();
        }

        $data = $catalog->listing($request, $category, $child);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('storefront.shop.partials.grid', ['products' => $data['products']])->render(),
                'count' => $data['products']->total(),
            ]);
        }

        return view('storefront.shop.index', [
            'category' => $category,
            'subcategory' => $child,
            'products' => $data['products'],
            'filters' => $data['filters'],
            'heading' => $child?->name ?: $category->name,
        ]);
    }

    public function categories()
    {
        $subcategories = Category::query()
            ->active()
            ->withCatalogProducts()
            ->whereNotNull('parent_id')
            ->whereHas('parent', fn ($parent) => $parent->active()->withCatalogProducts())
            ->with('parent')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('storefront.shop.categories', [
            'subcategories' => $subcategories,
            'seoTitle' => 'Shop by Category | '.store_name(),
            'seoDescription' => 'Browse every '.storefront_collection_names().' line at '.store_name().', with '.storefront_product_types().' delivered across India on Cash on Delivery.',
        ]);
    }
}
