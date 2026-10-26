<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdsenseContentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_categories_are_hidden_from_nav_sitemap_and_browse(): void
    {
        $apparel = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $emptyParent = Category::query()->create([
            'name' => 'Rossy Lustre',
            'slug' => 'jewellery',
            'is_active' => true,
            'display_order' => 2,
        ]);
        $emptyChild = Category::query()->create([
            'parent_id' => $apparel->id,
            'name' => 'Little Apparel Line',
            'slug' => 'kids-clothing',
            'is_active' => true,
            'display_order' => 3,
        ]);
        $liveChild = Category::query()->create([
            'parent_id' => $apparel->id,
            'name' => 'Mens Apparel Line',
            'slug' => 'mens-clothing',
            'is_active' => true,
            'display_order' => 1,
        ]);

        Product::query()->create([
            'category_id' => $apparel->id,
            'sub_category_id' => $liveChild->id,
            'name' => 'Canvas Work Shirt',
            'slug' => 'canvas-work-shirt',
            'short_description' => 'Everyday shirt with clear photos and fit notes.',
            'description' => str_repeat('Useful clothing detail for shoppers. ', 40),
            'mrp' => 1299,
            'selling_price' => 999,
            'stock_quantity' => 4,
            'status' => ProductStatus::Active,
        ]);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('Mens Apparel Line');
        $home->assertDontSee('Rossy Lustre');
        $home->assertDontSee('Little Apparel Line');

        $homeCollections = $this->get(route('home'));
        $homeCollections->assertOk();
        $homeCollections->assertSee('Mens Apparel Line');
        $homeCollections->assertDontSee('Little Apparel Line');
        $homeCollections->assertDontSee('Rossy Lustre');

        $this->get('/categories')->assertRedirect('/');

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk();
        $sitemap->assertSee('/fashion');
        $sitemap->assertSee('/fashion/mens-clothing');
        $sitemap->assertDontSee('/jewellery');
        $sitemap->assertDontSee('kids-clothing');

        $this->assertTrue($emptyParent->fresh()->is_active);
        $this->assertFalse($emptyParent->hasCatalogProducts());
        $this->assertFalse($emptyChild->hasCatalogProducts());

        $this->get('/fashion/kids-clothing')->assertNotFound();
        $this->get('/fashion/mens-clothing')->assertOk();
    }

    public function test_sitemap_only_lists_urls_that_open(): void
    {
        $apparel = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $liveChild = Category::query()->create([
            'parent_id' => $apparel->id,
            'name' => 'Mens Apparel Line',
            'slug' => 'mens-clothing',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $emptyChild = Category::query()->create([
            'parent_id' => $apparel->id,
            'name' => 'Cotton Workday Shirts',
            'slug' => 'cotton-workday-shirts',
            'is_active' => true,
            'display_order' => 2,
        ]);

        Product::query()->create([
            'category_id' => $apparel->id,
            'sub_category_id' => $liveChild->id,
            'name' => 'Canvas Work Shirt',
            'slug' => 'canvas-work-shirt',
            'mrp' => 1299,
            'selling_price' => 999,
            'stock_quantity' => 4,
            'status' => ProductStatus::Active,
        ]);

        Page::query()->create([
            'title' => 'Orphan Page',
            'slug' => 'orphan-unrouted-page',
            'content' => 'Should not appear in sitemap.',
            'is_active' => true,
        ]);
        Page::query()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => 'Privacy copy.',
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertSee('/fashion/mens-clothing');
        $response->assertSee('canvas-work-shirt');
        $response->assertSee('/privacy-policy');
        $response->assertDontSee('cotton-workday-shirts');
        $response->assertDontSee('orphan-unrouted-page');

        preg_match_all('#<loc>([^<]+)</loc>#', $response->getContent(), $matches);
        foreach ($matches[1] as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $this->get($path)->assertOk();
        }
    }

    public function test_category_listing_shows_guide_copy(): void
    {
        $bags = Category::query()->create([
            'name' => 'Rossy Carry',
            'slug' => 'bags',
            'is_active' => true,
            'description' => 'Rossy Carry bags for everyday handbags and occasion clutches.',
            'seo_content' => "Rossy Carry focuses on handbags you can actually use.\n\nHow to choose\nStructured bags suit workdays.",
        ]);

        Product::query()->create([
            'category_id' => $bags->id,
            'name' => 'Evening Beaded Clutch',
            'slug' => 'evening-beaded-clutch',
            'mrp' => 1999,
            'selling_price' => 1499,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->get('/bags');
        $response->assertOk();
        $response->assertSee('Rossy Carry bags for everyday handbags and occasion clutches.');
        $response->assertSee('About Rossy Carry');
        $response->assertSee('How to choose');
    }

    public function test_prepare_command_keeps_ads_off_and_expands_thin_copy(): void
    {
        Setting::put('adsense_enabled', true, 'store');

        $category = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
            'description' => 'Short',
            'seo_content' => 'Thin',
        ]);
        Category::query()->create([
            'name' => 'Empty Desk',
            'slug' => 'gift-items',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Soft Cotton Shirt',
            'slug' => 'soft-cotton-shirt',
            'short_description' => 'Soft cotton shirt.',
            'description' => 'A soft cotton shirt for daily wear.',
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);

        Artisan::call('shop:prepare-adsense-content');

        $this->assertSame('0', (string) Setting::query()->where('key', 'adsense_enabled')->value('value'));
        $this->assertFalse((bool) Category::query()->where('slug', 'gift-items')->value('is_active'));
        $this->assertGreaterThan(300, strlen((string) $category->fresh()->seo_content));
        $this->assertGreaterThanOrEqual(1000, strlen((string) $product->fresh()->description));
        $this->assertSame(0, Blog::query()->where('is_published', true)->count());
    }
}
