<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('shop:replace-stride-lines {--dry-run : Preview without writing}')]
#[Description('Remove Mens/Womens Stride Line categories and their products; add Ethnic Tops, Co-ord Ethnic Sets, Cotton Workday Shirts under Rossy Apparel')]
class ReplaceStrideLinesCommand extends Command
{
    /** @var list<string> */
    private const array REMOVE_SLUGS = ['mens-footwear', 'womens-footwear'];

    /**
     * @var array<string, array{name: string, description: string, seo_title: string, seo_description: string, seo_content: string}>
     */
    private const array NEW_CATEGORIES = [
        'ethnic-tops' => [
            'name' => 'Ethnic Tops',
            'description' => 'Ethnic tops for daily wear and festive plans — embroidered, printed and easy-to-style pieces.',
            'seo_title' => 'Ethnic Tops | Rossy Apparel | Black Rossy',
            'seo_description' => 'Shop ethnic tops at Black Rossy — embroidered and printed styles with clear photos and Cash on Delivery across India.',
            'seo_content' => <<<'TXT'
Ethnic Tops is a Rossy Apparel line for blouses, kurtis and statement tops that work for family gatherings, festivals and dressed-up everyday looks.

How to choose
Look at neckline, sleeve length and embroidery density in the photos. Heavier mirrorwork and brighter prints suit festive evenings; lighter prints suit office-to-evening wear. Read each product page for fabric and care notes before you order.

Shopping tips
Compare similar tops in this line for colour and length. Guest checkout and Cash on Delivery are available on eligible pin codes, with free shipping on every Black Rossy order. Eligible unused returns follow the Return Policy window.
TXT,
        ],
        'co-ord-ethnic-sets' => [
            'name' => 'Co-ord Ethnic Sets',
            'description' => 'Matching ethnic co-ord sets so you can wear a ready outfit without hunting for separate bottoms.',
            'seo_title' => 'Co-ord Ethnic Sets | Rossy Apparel | Black Rossy',
            'seo_description' => 'Shop co-ord ethnic sets at Black Rossy — coordinated tops and bottoms with clear photos and Cash on Delivery.',
            'seo_content' => <<<'TXT'
Co-ord Ethnic Sets brings matching shirt-and-pant or kurti-style outfits together so shopping stays simple. Each listing shows how the set looks as a full look, not only a single piece.

How to choose
Check print placement across top and bottom, sleeve style and whether the set feels festive or everyday. Co-ords save time for travel and functions; if you prefer mix-and-match, browse Ethnic Tops or Cotton Workday Shirts instead.

Care and delivery
Follow care notes on each product — many printed sets last longer with mild wash and shade drying. Packing is usually 1–2 working days with free shipping and Cash on Delivery on eligible pin codes.
TXT,
        ],
        'cotton-workday-shirts' => [
            'name' => 'Cotton Workday Shirts',
            'description' => 'Breathable workday shirts for office, college and everyday Indian weather.',
            'seo_title' => 'Cotton Workday Shirts | Rossy Apparel | Black Rossy',
            'seo_description' => 'Shop cotton workday shirts at Black Rossy — clear photos, fit notes and Cash on Delivery across India.',
            'seo_content' => <<<'TXT'
Cotton Workday Shirts focuses on button-downs and everyday shirts you can wear to work, college or weekend outings. Listings highlight collar style, sleeve length, print and colour so you can decide from your phone.

Fit and pairing
Full sleeves layer well in AC offices; lighter weaves suit humid days. Pair solids with jeans or trousers, and keep bold prints with quieter bottoms. Read size notes on each product if you are between sizes.

Order with confidence
Guest checkout, free shipping and Cash on Delivery on eligible pin codes. Keep tags if you may need an eligible return within the window on our Return Policy page.
TXT,
        ],
    ];

    public function handle(ImageService $images): int
    {
        $dry = (bool) $this->option('dry-run');
        $apparel = Category::query()->where('slug', 'fashion')->whereNull('parent_id')->first();

        if ($apparel === null) {
            $this->error('Rossy Apparel (fashion) parent category not found.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($dry, $apparel, $images): void {
            $removedIds = Category::query()->whereIn('slug', self::REMOVE_SLUGS)->pluck('id')->all();
            $this->removeStrideLines($dry, $images);
            $this->deactivateEmptyFootwearParent($dry);
            $this->createApparelLines($dry, $apparel);
            $this->scrubHomepageCategoryIds($dry, $removedIds);
        });

        Cache::forget('shop.nav.category_ids');
        Cache::forget('shop.collections');

        $this->info($dry ? 'Dry run complete — no database writes.' : 'Stride lines removed and new apparel categories ready.');

        return self::SUCCESS;
    }

    private function removeStrideLines(bool $dry, ImageService $images): void
    {
        $categories = Category::query()
            ->whereIn('slug', self::REMOVE_SLUGS)
            ->get();

        foreach ($categories as $category) {
            $products = Product::query()
                ->with('images')
                ->where(function ($query) use ($category): void {
                    $query->where('sub_category_id', $category->id)
                        ->orWhere('category_id', $category->id);
                })
                ->get();

            $this->line(($dry ? 'Would delete' : 'Deleting')." {$products->count()} product(s) from {$category->slug}");

            foreach ($products as $product) {
                if ($dry) {
                    continue;
                }

                foreach ($product->images as $image) {
                    $images->deleteMany([
                        $image->path_thumb,
                        $image->path_medium,
                        $image->path_large,
                    ]);
                }

                $product->tags()->detach();
                $product->delete();
            }

            $this->line(($dry ? 'Would remove' : 'Removing')." category {$category->slug}");

            if (! $dry) {
                $category->delete();
            }
        }
    }

    private function deactivateEmptyFootwearParent(bool $dry): void
    {
        $footwear = Category::query()->where('slug', 'footwear')->whereNull('parent_id')->first();
        if ($footwear === null) {
            return;
        }

        $remaining = Product::query()->where('category_id', $footwear->id)->count();
        $childProducts = Product::query()
            ->whereIn('sub_category_id', $footwear->children()->pluck('id'))
            ->count();

        if ($remaining + $childProducts > 0) {
            return;
        }

        $this->line(($dry ? 'Would deactivate' : 'Deactivating').' empty Rossy Stride parent');

        if (! $dry) {
            $footwear->update([
                'is_active' => false,
                'show_on_homepage' => false,
            ]);
        }
    }

    private function createApparelLines(bool $dry, Category $apparel): void
    {
        $order = (int) Category::query()->where('parent_id', $apparel->id)->max('display_order');

        foreach (self::NEW_CATEGORIES as $slug => $data) {
            $existing = Category::query()->where('slug', $slug)->first();
            if ($existing !== null) {
                $this->warn("Category already exists: {$slug} — refreshing copy/active flags.");
                if (! $dry) {
                    $existing->update([
                        'parent_id' => $apparel->id,
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'seo_title' => $data['seo_title'],
                        'seo_description' => $data['seo_description'],
                        'seo_content' => $data['seo_content'],
                        'is_active' => true,
                        'show_on_homepage' => false,
                    ]);
                }

                continue;
            }

            $order++;
            $this->line(($dry ? 'Would create' : 'Creating')." {$slug}");

            if ($dry) {
                continue;
            }

            Category::query()->create([
                'parent_id' => $apparel->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
                'seo_title' => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'seo_content' => $data['seo_content'],
                'seo_keywords' => Str::of($data['name'])->lower()->replace(' ', ', ').', black rossy',
                'is_active' => true,
                'show_on_homepage' => false,
                'display_order' => $order,
            ]);
        }
    }

    /**
     * @param  list<int>  $removedIds
     */
    private function scrubHomepageCategoryIds(bool $dry, array $removedIds): void
    {
        if ($removedIds === []) {
            return;
        }

        $section = HomepageSection::query()->where('key', 'featured_categories')->first();
        if ($section === null) {
            return;
        }

        $config = is_array($section->config) ? $section->config : [];
        $original = array_map('intval', $config['category_ids'] ?? []);
        $ids = array_values(array_filter(
            $original,
            fn (int $id) => ! in_array($id, $removedIds, true)
        ));

        if ($ids === $original) {
            return;
        }

        $this->line(($dry ? 'Would scrub' : 'Scrubbing').' homepage featured category ids');

        if (! $dry) {
            $config['category_ids'] = $ids;
            $section->update(['config' => $config]);
        }
    }
}
