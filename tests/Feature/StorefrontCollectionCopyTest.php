<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCollectionCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_copy_follows_category_toggles(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-copy@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $fashion = Category::query()->create(['name' => 'Rossy Apparel', 'slug' => 'fashion', 'is_active' => true, 'display_order' => 1]);
        $jewellery = Category::query()->create(['name' => 'Rossy Lustre', 'slug' => 'jewellery', 'is_active' => false, 'display_order' => 2]);
        $bags = Category::query()->create(['name' => 'Rossy Carry', 'slug' => 'bags', 'is_active' => true, 'display_order' => 3]);
        $this->stock($fashion, 'visible-apparel-tee');
        $this->stock($bags, 'visible-carry-tote');
        $this->stock($jewellery, 'hidden-lustre-ring');

        $hidden = $this->get(route('about'));
        $hidden->assertOk();
        $hidden->assertSee('A curated boutique for Rossy Apparel and Carry.', false);
        $hidden->assertSee('Search clothing, bags...', false);
        $hidden->assertSee('Live collections centre on clothing and bags', false);
        $hidden->assertSee('Handbags and clutches for daily carry and occasion looks', false);
        $hidden->assertDontSee('Lustre');
        $hidden->assertDontSee('jewellery,');
        $hidden->assertDontSee('Footwear chosen for everyday comfort');
        $hidden->assertDontSee('Fashion jewellery for everyday outfits');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.categories.active', $jewellery), ['value' => 1])
            ->assertRedirect();

        $shown = $this->get(route('about'));
        $shown->assertSee('A curated boutique for Rossy Apparel, Lustre and Carry.', false);
        $shown->assertSee('Search clothing, jewellery, bags...', false);
    }

    public function test_default_seo_title_names_only_active_collections(): void
    {
        $fashion = Category::query()->create(['name' => 'Rossy Apparel', 'slug' => 'fashion', 'is_active' => true, 'display_order' => 1]);
        Category::query()->create(['name' => 'Rossy Stride', 'slug' => 'footwear', 'is_active' => false, 'display_order' => 2]);
        $this->stock($fashion, 'seo-apparel-shirt');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>'.store_name().' | Rossy Apparel</title>', false);
        $response->assertSee('Shop clothing at '.store_name(), false);
        $response->assertDontSee('Stride');
        $response->assertDontSee('footwear');
    }

    private function stock(Category $category, string $slug): void
    {
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Stock '.$slug,
            'slug' => $slug,
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);
    }
}
