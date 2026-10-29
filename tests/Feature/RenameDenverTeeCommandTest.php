<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RenameDenverTeeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_renames_colorado_denver_tee(): void
    {
        $category = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Colorado Denver Graphic Raglan Tee — Lime Green & Purple',
            'slug' => 'mens-colorado-denver-raglan-tee-lime-purple',
            'short_description' => 'Lime green and purple Colorado Denver raglan tees.',
            'description' => 'Mens Colorado Denver Graphic Raglan Tee with varsity print.',
            'seo_title' => 'Mens Colorado Denver Raglan Tee | Black Rossy',
            'seo_description' => 'Shop the mens Colorado Denver graphic raglan tee.',
            'mrp' => 799,
            'selling_price' => 299,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
        ]);

        Artisan::call('shop:rename-denver-tee');

        $product->refresh();

        $this->assertSame('Lime Purple Varsity Stripe Raglan Graphic Tee', $product->name);
        $this->assertSame('mens-lime-purple-varsity-stripe-raglan-graphic-tee', $product->slug);
        $this->assertStringNotContainsString('Colorado', $product->name.$product->description.$product->short_description);
        $this->assertStringNotContainsString('Denver', $product->name.$product->description.$product->short_description);
        $this->assertGreaterThanOrEqual(180, str_word_count(strip_tags($product->description)));
    }
}
