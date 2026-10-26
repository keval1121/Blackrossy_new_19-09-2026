<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReplaceStrideLinesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_stride_lines_and_adds_apparel_categories(): void
    {
        $apparel = Category::query()->create([
            'name' => 'Rossy Apparel',
            'slug' => 'fashion',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $footwear = Category::query()->create([
            'name' => 'Rossy Stride',
            'slug' => 'footwear',
            'is_active' => true,
            'show_on_homepage' => true,
            'display_order' => 2,
        ]);
        $mens = Category::query()->create([
            'parent_id' => $footwear->id,
            'name' => 'Mens Stride Line',
            'slug' => 'mens-footwear',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $womens = Category::query()->create([
            'parent_id' => $footwear->id,
            'name' => 'Womens Stride Line',
            'slug' => 'womens-footwear',
            'is_active' => true,
            'display_order' => 2,
        ]);

        Product::query()->create([
            'category_id' => $footwear->id,
            'sub_category_id' => $mens->id,
            'name' => 'Mens Slide',
            'slug' => 'mens-slide-test',
            'mrp' => 999,
            'selling_price' => 699,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);
        Product::query()->create([
            'category_id' => $footwear->id,
            'sub_category_id' => $womens->id,
            'name' => 'Womens Flat',
            'slug' => 'womens-flat-test',
            'mrp' => 999,
            'selling_price' => 699,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);

        Artisan::call('shop:replace-stride-lines');

        $this->assertDatabaseMissing('categories', ['slug' => 'mens-footwear']);
        $this->assertDatabaseMissing('categories', ['slug' => 'womens-footwear']);
        $this->assertDatabaseMissing('products', ['slug' => 'mens-slide-test']);
        $this->assertDatabaseMissing('products', ['slug' => 'womens-flat-test']);
        $this->assertFalse((bool) $footwear->fresh()->is_active);

        foreach (['ethnic-tops', 'co-ord-ethnic-sets', 'cotton-workday-shirts'] as $slug) {
            $category = Category::query()->where('slug', $slug)->first();
            $this->assertNotNull($category);
            $this->assertSame($apparel->id, $category->parent_id);
            $this->assertTrue($category->is_active);
            $this->assertGreaterThan(200, strlen((string) $category->seo_content));
        }
    }
}
