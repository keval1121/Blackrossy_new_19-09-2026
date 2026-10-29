<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('shop:rename-denver-tee {--dry-run : Preview without writing}')]
#[Description('Rename the Colorado Denver raglan tee to a neutral varsity name')]
class RenameDenverTeeCommand extends Command
{
    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $newSlug = 'mens-lime-purple-varsity-stripe-raglan-graphic-tee';

        $product = Product::query()
            ->where('slug', 'mens-colorado-denver-raglan-tee-lime-purple')
            ->orWhere('slug', $newSlug)
            ->orWhere('name', 'like', '%Colorado Denver%')
            ->first();

        if ($product === null) {
            $this->warn('Colorado Denver tee not found — nothing to rename.');

            return self::SUCCESS;
        }

        if (
            $product->slug !== $newSlug
            && Product::query()->where('slug', $newSlug)->where('id', '!=', $product->id)->exists()
        ) {
            $this->error("Slug already taken: {$newSlug}");

            return self::FAILURE;
        }

        $payload = [
            'name' => 'Lime Purple Varsity Stripe Raglan Graphic Tee',
            'slug' => $newSlug,
            'short_description' => 'Lime green and purple raglan tees with white striped sleeves, soft crew neck and a casual varsity graphic that pairs easily with jeans for daily wear.',
            'description' => <<<'TXT'
Mens Lime Purple Varsity Stripe Raglan Graphic Tee – Casual Everyday Wear
Elevate your everydaywardrobe with this stylish short-sleeve raglan tee for men from Black Rossy. Soft lime green and purple body colours sit with crisp white sleeves, twin body-matching stripes near the cuff and a neat ribbed crew neck for a clean sporty look. Arched lettering, premium and quality marks and a bold year-style centre print add a varsity graphic without making the tee feel oversized or loud. The raglan sleeve cut gives the shoulders an easy athletic line that works under a light overshirt or on its own with jeans. Lime green brings a fresh daytime pop against dark denim and sneakers, while purple gives a cooler tone for college, shopping, travel and weekend plans. Soft everyday jersey keeps the drape light and comfortable from morning campus runs to café meet-ups and relaxed evenings out. Pair it with black jeans, chinos or a simple casual jacket, then finish with sneakers or loafers depending on the occasion.
Style tags: Mens raglan tee, lime purple varsity tee, striped sleeve casual t-shirt, everyday menswear.
TXT,
            'seo_title' => 'Mens Lime Purple Varsity Raglan Tee | Black Rossy',
            'seo_description' => 'Shop the mens lime purple varsity stripe raglan graphic tee at Black Rossy — sizes S–XXL, free shipping and COD.',
        ];

        if ($dry) {
            $this->line("Would rename #{$product->id} → {$payload['name']} ({$payload['slug']})");

            return self::SUCCESS;
        }

        $product->update($payload);
        Cache::forget('shop.collections');
        $this->info("Renamed product #{$product->id} to {$payload['name']}");

        return self::SUCCESS;
    }
}
