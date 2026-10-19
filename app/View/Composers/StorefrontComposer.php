<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Services\CartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class StorefrontComposer
{
    public function compose(View $view): void
    {
        try {
            $categories = Cache::remember('shop.nav.category_ids', 600, function () {
                return Category::query()
                    ->active()
                    ->parents()
                    ->withCatalogProducts()
                    ->orderBy('display_order')
                    ->pluck('id')
                    ->all();
            });

            $navCategories = Category::query()
                ->with(['children' => fn ($query) => $query->active()->withCatalogProducts()->with('parent')->orderBy('display_order')])
                ->whereIn('id', $categories)
                ->orderBy('display_order')
                ->get();

            $navSubcategories = $navCategories
                ->flatMap(fn (Category $category) => $category->children)
                ->values();

            $cartCount = app(CartService::class)->count();
        } catch (Throwable) {
            $navCategories = collect();
            $navSubcategories = collect();
            $cartCount = 0;
        }

        $view->with([
            'navCategories' => $navCategories,
            'navSubcategories' => $navSubcategories,
            'cartCount' => $cartCount,
            'whatsappNumber' => preg_replace('/\D+/', '', (string) setting('whatsapp_number', '')),
        ]);
    }
}
