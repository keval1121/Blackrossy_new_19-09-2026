<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Ad;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdPlacementPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const string LOADER = 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('adsense_client_id', 'ca-pub-1983284873156439', 'store');
        Setting::put('adsense_enabled', true, 'store');

        foreach (array_keys(config('shop.ad_positions')) as $position) {
            Ad::query()->create([
                'name' => $position,
                'position' => $position,
                'code' => '<ins class="adsbygoogle" data-ad-slot="'.$position.'"></ins>',
                'is_active' => true,
            ]);
        }

        HomepageSection::query()->firstOrCreate(
            ['key' => 'featured_products'],
            ['title' => 'Featured', 'is_enabled' => true, 'display_order' => 2, 'config' => []]
        );

        $this->category = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
            'seo_content' => 'Everyday clothing picked for Indian weather.',
        ]);

        foreach (range(1, 9) as $number) {
            $this->product = Product::query()->create([
                'category_id' => $this->category->id,
                'name' => 'Linen Shirt '.$number,
                'slug' => 'linen-shirt-'.$number,
                'description' => 'Breathable linen shirt.',
                'mrp' => 999,
                'selling_price' => 799,
                'stock_quantity' => 5,
                'status' => ProductStatus::Active,
            ]);
        }
    }

    public function test_product_page_has_no_ad_beside_the_buy_buttons(): void
    {
        $response = $this->get(route('product.show', $this->product));

        $response->assertOk();
        $response->assertDontSee('data-ad-slot="product_middle"', false);
        $response->assertSeeInOrder(['Buy now', 'Description', 'Advertisement', 'data-ad-slot="product_bottom"'], false);
    }

    public function test_removed_placements_never_render(): void
    {
        foreach ([route('home'), route('search', ['q' => 'Linen']), $this->category->url()] as $url) {
            $response = $this->get($url);

            $response->assertOk();

            foreach (['home_top', 'search_middle', 'category_middle', 'category_bottom', 'listing_bottom', 'blog_top', 'blog_middle', 'blog_bottom'] as $position) {
                $response->assertDontSee('data-ad-slot="'.$position.'"', false);
            }
        }

        $this->assertSame(
            ['home_middle', 'home_bottom', 'category_top', 'listing_middle', 'product_bottom'],
            array_keys(config('shop.ad_positions'))
        );
    }

    public function test_category_ads_sit_inside_content_and_skip_ajax_refreshes(): void
    {
        $page = $this->get($this->category->url());

        $page->assertOk();
        $page->assertSeeInOrder([
            'data-ad-slot="category_top"',
            'Linen Shirt',
            'data-ad-slot="listing_middle"',
            'About Rossy Apparel',
            'Everyday clothing picked for Indian weather.',
        ], false);
        $productLink = 'href="'.url('/product/linen-shirt-');
        $html = $page->getContent();
        $linksPerCard = substr_count($html, $productLink) / 9;
        $this->assertSame(6 * $linksPerCard, substr_count(strstr($html, 'data-ad-slot="listing_middle"', true), $productLink));
        $page->assertDontSee('9 items');

        $ajax = $this->getJson($this->category->url(), ['X-Requested-With' => 'XMLHttpRequest']);

        $ajax->assertOk();
        $this->assertStringNotContainsString('data-ad-slot', $ajax->json('html'));
    }

    public function test_ad_loader_is_skipped_on_private_and_error_pages(): void
    {
        $this->get(route('home'))
            ->assertSee(self::LOADER, false)
            ->assertSee('<meta name="google-adsense-account" content="ca-pub-1983284873156439">', false);

        $this->get(route('cart.index'))->assertOk()->assertDontSee(self::LOADER, false);
        $this->get(route('track'))->assertOk()->assertDontSee(self::LOADER, false);
        $this->get('/no-such-page/at-all')->assertNotFound()->assertDontSee(self::LOADER, false);

        $this->postJson(route('cart.add'), ['product_id' => $this->product->id, 'quantity' => 1])->assertOk();

        $this->get(route('checkout.index'))->assertOk()->assertDontSee(self::LOADER, false);
    }

    public function test_verification_tag_stays_while_ads_are_switched_off(): void
    {
        Setting::put('adsense_enabled', false, 'store');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="google-adsense-account" content="ca-pub-1983284873156439">', false)
            ->assertDontSee(self::LOADER, false)
            ->assertDontSee('data-ad-slot', false);

        $this->get($this->category->url())
            ->assertOk()
            ->assertDontSee('col-span-2 lg:col-span-3', false);
    }
}
