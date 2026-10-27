<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductFormDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_prefills_default_size_variants_and_hides_short_description(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-form-defaults@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.create'));

        $response->assertOk();
        $response->assertDontSee('Short description', false);
        $response->assertSee('name="variants[0][label]"', false);
        $response->assertSee('value="Size: S"', false);
        $response->assertSee('value="Size: XXL"', false);
        $response->assertSee('value="001"', false);
        $response->assertSee('value="005"', false);
        $response->assertSee('value="200"', false);
    }

    public function test_storefront_product_page_does_not_show_short_description(): void
    {
        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel-no-short',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'No Short Shirt',
            'slug' => 'no-short-shirt',
            'short_description' => 'HIDDEN_SHORT_COPY',
            'description' => 'Full product description for shoppers and SEO.',
            'mrp' => 999,
            'selling_price' => 499,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->get(route('product.show', $product));

        $response->assertOk();
        $response->assertDontSee('HIDDEN_SHORT_COPY');
        $response->assertSee('Full product description for shoppers and SEO.');
    }
}
