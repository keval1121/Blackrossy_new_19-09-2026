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
            // Hide empty lines from crawlers / AdSense until they have active products.
            abort_unless($child->hasCatalogProducts(), 404);
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
}
