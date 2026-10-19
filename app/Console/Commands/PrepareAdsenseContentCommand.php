<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Signature('shop:prepare-adsense-content {--dry-run : Preview changes without writing}')]
#[Description('Hide empty categories, expand category/about copy, thicken thin product pages; keeps ads off and does not publish blogs')]
class PrepareAdsenseContentCommand extends Command
{
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->deactivateEmptyCategories($dry);
        $this->applyCategoryGuides($dry);
        $this->enrichThinActiveProducts($dry);
        $this->expandAboutPage($dry);
        $this->ensureAdsStayOff($dry);

        Cache::forget('shop.nav.category_ids');
        Cache::forget('shop.collections');
        Cache::forget('shop.settings');
        Cache::forget('shop.ads');

        $this->info($dry ? 'Dry run complete — no database writes.' : 'AdSense content prep complete (blog untouched, ads kept off).');

        return self::SUCCESS;
    }

    private function deactivateEmptyCategories(bool $dry): void
    {
        $deactivated = 0;

        Category::query()->orderBy('id')->each(function (Category $category) use ($dry, &$deactivated): void {
            if (! $category->is_active) {
                return;
            }

            if ($category->hasCatalogProducts()) {
                return;
            }

            $this->line('Deactivate empty category: '.$category->slug);
            $deactivated++;

            if (! $dry) {
                $category->update([
                    'is_active' => false,
                    'show_on_homepage' => false,
                ]);
            }
        });

        $this->info(($dry ? 'Would deactivate' : 'Deactivated')." {$deactivated} empty categor(ies).");
    }

    private function applyCategoryGuides(bool $dry): void
    {
        $path = database_path('data/category_guides.php');
        if (! is_file($path)) {
            $this->error('Missing '.$path);

            return;
        }

        /** @var array<string, array{description: string, seo_content: string, seo_title: string, seo_description: string}> $guides */
        $guides = require $path;
        $updated = 0;

        Category::query()->orderBy('id')->each(function (Category $category) use ($guides, $dry, &$updated): void {
            $guide = $guides[$category->slug] ?? null;
            if ($guide === null) {
                $this->warn('No guide for category slug: '.$category->slug);

                return;
            }

            $updated++;
            if ($dry) {
                $this->line($category->slug.' → '.str_word_count($guide['seo_content']).' words');

                return;
            }

            $category->update([
                'description' => trim($guide['description']),
                'seo_content' => trim($guide['seo_content']),
                'seo_title' => trim($guide['seo_title']),
                'seo_description' => trim($guide['seo_description']),
            ]);
        });

        $this->info(($dry ? 'Would update' : 'Updated')." {$updated} category guide(s).");
    }

    private function enrichThinActiveProducts(bool $dry): void
    {
        $minChars = 1000;
        $updated = 0;

        Product::query()
            ->where('status', ProductStatus::Active)
            ->orderBy('id')
            ->each(function (Product $product) use ($dry, $minChars, &$updated): void {
                $base = trim((string) $product->description);
                if (strlen($base) >= $minChars) {
                    return;
                }

                $extra = $this->buyerGuideAppendix($product);
                $description = trim($base."\n\n".$extra);

                if (strlen($description) < $minChars) {
                    $description .= "\n\n".$this->shippingTrustAppendix();
                }

                $short = trim((string) $product->short_description);
                if (strlen($short) < 120) {
                    $short = Str::limit(preg_replace('/\s+/', ' ', strip_tags($description)) ?: $product->name, 160, '');
                }

                $updated++;
                if ($dry) {
                    $this->line($product->slug.' → '.strlen($description).' chars');

                    return;
                }

                $product->update([
                    'short_description' => $short,
                    'description' => $description,
                    'seo_title' => $product->seo_title ?: ($product->name.' | Buy Online | Black Rossy'),
                    'seo_description' => $product->seo_description ?: Str::limit($short, 155),
                ]);
            });

        $this->info(($dry ? 'Would enrich' : 'Enriched')." {$updated} thin active product(s).");
    }

    private function buyerGuideAppendix(Product $product): string
    {
        $name = $product->name;
        $category = $product->category?->name ?: 'this collection';
        $sub = $product->subCategory?->name;

        $line = $sub
            ? "This listing sits in {$sub} under {$category} at Black Rossy."
            : "This listing sits in {$category} at Black Rossy.";

        return <<<TXT
Who it suits
{$name} is meant for shoppers who want a clear sense of colour, finish and everyday usefulness before they order. {$line} Use the photos to judge scale and detail, then match the piece to outfits you already wear — office days, travel, family gatherings or festive plans.

How to decide
Compare this product with similar items in the same line for colour family and embellishment level. If you are unsure about size or fit, read any size notes on this page and message us from Contact with your usual brand size. Phone screens can shift colour slightly; natural light photos on this page are the best guide.

Care and confidence
Follow the care notes above when present. Keep tags and packaging if you may need an eligible return within the window on our Return Policy page. Guest checkout and Cash on Delivery are available on eligible Indian pin codes, and shipping is free on every Black Rossy order.
TXT;
    }

    private function shippingTrustAppendix(): string
    {
        return 'Most orders are packed in 1–2 working days and delivered in about 2–5 days depending on your pin code and courier route. Track updates with the mobile number used at checkout, and reach WhatsApp or Contact support if you need help after delivery.';
    }

    private function expandAboutPage(bool $dry): void
    {
        $content = <<<'TXT'
Black Rossy is an India-based online boutique for fashion clothing, footwear and bags. We built the store for shoppers who want honest product photos, practical descriptions and a checkout that does not force an account — with Cash on Delivery on eligible pin codes.

Our story
We started Black Rossy to make everyday and festive shopping simpler. Too many product pages show little more than a price and a stock photo. Here, each live listing aims to explain who the piece is for, when to wear it, and how to care for it, so you can decide with confidence from your phone.

What we sell
Our live collections focus on Rossy Apparel (clothing), Rossy Stride (footwear) and Rossy Carry (handbags and clutches). Category pages include buying guidance — not only a product grid — so you can learn how to choose before you filter by price. Jewellery, beauty, home and gift desks may appear when those assortments are stocked; until then we keep empty lines off the public menu so you only browse what you can buy.

How shopping works
Browse a collection, open a product, add it to your cart, and checkout as a guest with your name, mobile number and delivery address. Shipping is free on every order. Packing usually takes 1–2 working days, with delivery often in 2–5 days depending on your pin code. Pay the courier only after the parcel reaches you on eligible COD routes.

Policies and support
Shipping, returns, refunds, cancellation, privacy and cookies are published as full pages on this website. Eligible unused returns follow a 7-day window when tags and packaging are intact — jewellery and a few personal items can have extra limits, explained on the Return Policy page. For order help, use Contact, email, phone or WhatsApp with your order number.

Why this matters
We want Black Rossy to be useful even when you are only researching an outfit: clear navigation, readable policies, and product copy written for real Indian occasions. That is the standard we hold ourselves to as we grow the catalogue.
TXT;

        $page = Page::query()->where('slug', 'about')->first();
        if ($page === null) {
            $this->warn('About page row missing — blade template still carries the public About content.');

            return;
        }

        if ($dry) {
            $this->line('Would expand about page to '.strlen($content).' chars');

            return;
        }

        $page->update([
            'content' => $content,
            'seo_title' => 'About Us | Black Rossy',
            'seo_description' => 'Learn about Black Rossy — an India COD boutique for clothing, footwear and bags with clear product guidance and guest checkout.',
            'is_active' => true,
        ]);

        $this->info('Expanded About Us CMS page.');
    }

    private function ensureAdsStayOff(bool $dry): void
    {
        if ($dry) {
            $this->line('Would keep adsense_enabled = 0');

            return;
        }

        Setting::put('adsense_enabled', false, 'store');
        $this->info('Confirmed adsense_enabled is off.');
    }
}
