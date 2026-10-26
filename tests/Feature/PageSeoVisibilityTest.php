<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSeoVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const string NOINDEX = '<meta name="robots" content="noindex';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $apparel = Category::query()->create(['name' => 'Rossy Apparel', 'slug' => 'fashion', 'is_active' => true]);

        HomepageSection::query()->firstOrCreate(
            ['key' => 'featured_products'],
            ['title' => 'Featured', 'is_enabled' => true, 'display_order' => 2, 'config' => []]
        );

        $this->product = Product::query()->create([
            'category_id' => $apparel->id,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt',
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);
    }

    public function test_contact_has_its_own_title_and_categories_redirects_home(): void
    {
        $homeTitle = '<title>'.store_name().' | Rossy Apparel</title>';

        $contact = $this->get(route('contact'));
        $contact->assertOk();
        $contact->assertSee('<title>Contact Us | '.store_name().'</title>', false);
        $contact->assertDontSee($homeTitle, false);
        $contact->assertSee('Send message');
        $contact->assertSee('<form method="post"', false);

        $this->get('/categories')->assertRedirect('/');
    }

    public function test_listing_titles_name_the_store_once_and_shop_description_reads_naturally(): void
    {
        $bags = Category::query()->create(['name' => 'Rossy Carry', 'slug' => 'bags', 'is_active' => true, 'seo_title' => 'Rossy Carry | '.store_name()]);
        $wallets = Category::query()->create(['parent_id' => $bags->id, 'name' => 'Pocket Carry Line', 'slug' => 'wallets', 'is_active' => true, 'seo_title' => 'Pocket Carry Line | '.store_name()]);
        $backpacks = Category::query()->create(['parent_id' => $bags->id, 'name' => 'Day Carry Line', 'slug' => 'backpacks', 'is_active' => true]);

        Product::query()->create([
            'category_id' => $bags->id,
            'sub_category_id' => $wallets->id,
            'name' => 'Slim Pocket Wallet',
            'slug' => 'slim-pocket-wallet',
            'mrp' => 699,
            'selling_price' => 499,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
        ]);
        Product::query()->create([
            'category_id' => $bags->id,
            'sub_category_id' => $backpacks->id,
            'name' => 'City Day Backpack',
            'slug' => 'city-day-backpack',
            'mrp' => 1599,
            'selling_price' => 1199,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);

        $this->get('/bags')->assertOk()->assertSee('<title>Rossy Carry | '.store_name().'</title>', false);
        $this->get('/bags/wallets')->assertOk()->assertSee('<title>Pocket Carry Line | '.store_name().'</title>', false);
        $this->get('/bags/backpacks')
            ->assertOk()
            ->assertSee('<title>Day Carry Line | '.store_name().'</title>', false)
            ->assertSee('Day Carry Line at '.store_name().' — free shipping on every order', false);

        $shop = $this->get(route('shop'));
        $shop->assertOk();
        $shop->assertSee('<title>Shop All | '.store_name().'</title>', false);
        $shop->assertSee('Browse all clothing and bags at '.store_name(), false);
        $shop->assertDontSee('Shop Shop All');

        foreach (['/bags', '/bags/wallets', '/bags/backpacks', route('shop')] as $url) {
            $this->get($url)->assertDontSee(store_name().' | '.store_name());
        }
    }

    public function test_private_shopping_pages_are_hidden_from_search_engines(): void
    {
        $this->get(route('cart.index'))->assertOk()->assertSee(self::NOINDEX, false);
        $this->get(route('track'))->assertOk()->assertSee(self::NOINDEX, false);

        $this->postJson(route('cart.add'), ['product_id' => $this->product->id, 'quantity' => 1])->assertOk();

        $this->get(route('checkout.index'))->assertOk()->assertSee(self::NOINDEX, false);
    }

    public function test_public_pages_stay_indexable(): void
    {
        foreach ([route('home'), route('shop'), route('contact'), route('product.show', $this->product)] as $url) {
            $this->get($url)->assertOk()->assertDontSee(self::NOINDEX, false);
        }
    }

    public function test_robots_file_only_blocks_admin(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertSee('Disallow: /admin');
        $response->assertDontSee('Disallow: /blog');
        $response->assertDontSee('Disallow: /cart');
        $response->assertDontSee('Disallow: /checkout');
    }
}
