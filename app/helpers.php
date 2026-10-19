<?php

use App\Models\Ad;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('shop.settings', 3600, function () {
            try {
                return Setting::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                return [];
            }
        });

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key];

        if (is_string($value) && in_array($value, ['0', '1'], true) && in_array($key, [
            'cod_enabled', 'otp_enabled', 'maintenance_mode', 'coupons_enabled',
            'reviews_enabled', 'adsense_enabled', 'restore_stock_on_cancel',
        ], true)) {
            return $value === '1';
        }

        return $value;
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $symbol = setting('currency_symbol', '₹');

        return $symbol.number_format($amount, $amount == floor($amount) ? 0 : 2);
    }
}

if (! function_exists('discount_percent')) {
    function discount_percent(float|int|null $mrp, float|int|null $price): int
    {
        $mrp = (float) $mrp;
        $price = (float) $price;

        if ($mrp <= 0 || $price >= $mrp) {
            return 0;
        }

        return (int) round((($mrp - $price) / $mrp) * 100);
    }
}

if (! function_exists('ad_code')) {
    function ad_code(string $position): ?string
    {
        // Never render placement ads until explicitly enabled (keeps review-safe).
        if (! setting('adsense_enabled', false)) {
            return null;
        }

        /** @var array<string, string|null> $ads */
        $ads = Cache::remember('shop.ads', 3600, function () {
            try {
                return Ad::query()
                    ->where('is_active', true)
                    ->pluck('code', 'position')
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        if (! is_array($ads)) {
            Cache::forget('shop.ads');

            return null;
        }

        $code = $ads[$position] ?? null;
        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        // Reject incomplete snippets (loader-only) — those belong in <head>, not placements.
        if (! str_contains($code, 'data-ad-slot')) {
            return null;
        }

        return $code;
    }
}

if (! function_exists('adsense_client_id')) {
    /**
     * Normalized AdSense client id, e.g. ca-pub-1983284873156439.
     */
    function adsense_client_id(): ?string
    {
        $raw = trim((string) setting('adsense_client_id', ''));
        if ($raw === '') {
            return null;
        }

        if (preg_match('/ca-pub-\d+/i', $raw, $m)) {
            return strtolower($m[0]);
        }

        if (preg_match('/pub-(\d+)/i', $raw, $m)) {
            return 'ca-pub-'.$m[1];
        }

        return null;
    }
}

if (! function_exists('adsense_publisher_id')) {
    /**
     * Publisher id for ads.txt, e.g. pub-1983284873156439.
     */
    function adsense_publisher_id(): ?string
    {
        $client = adsense_client_id();
        if ($client === null) {
            return null;
        }

        return str_replace('ca-', '', $client);
    }
}

if (! function_exists('ga_measurement_id')) {
    /**
     * Google Analytics 4 Measurement ID, e.g. G-ZGN19R100B.
     */
    function ga_measurement_id(): ?string
    {
        $raw = strtoupper(trim((string) setting('ga_measurement_id', '')));
        if ($raw === '') {
            return null;
        }

        return preg_match('/^G-[A-Z0-9]+$/', $raw) ? $raw : null;
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return (string) setting('website_name', config('app.name', 'Black Rossy'));
    }
}

if (! function_exists('storefront_collections')) {
    /**
     * Active top-level categories, so storefront copy only names what shoppers can actually browse.
     *
     * @return Collection<int, array{name: string, slug: string}>
     */
    function storefront_collections(): Collection
    {
        try {
            return collect(Cache::remember('shop.collections', 600, fn () => Category::query()
                ->active()
                ->parents()
                ->withCatalogProducts()
                ->orderBy('display_order')
                ->get(['name', 'slug'])
                ->map(fn (Category $category) => ['name' => $category->name, 'slug' => $category->slug])
                ->all()));
        } catch (Throwable) {
            return collect();
        }
    }
}

if (! function_exists('storefront_collection_names')) {
    /**
     * e.g. "Rossy Apparel, Lustre and Carry" — the shared first word is only kept on the first name.
     */
    function storefront_collection_names(string $finalGlue = ' and '): string
    {
        $names = storefront_collections()->pluck('name')->values();
        if ($names->isEmpty()) {
            return store_name();
        }

        $sharedPrefix = Str::before($names->first(), ' ').' ';
        if ($names->count() > 1 && $names->every(fn (string $name) => str_starts_with($name, $sharedPrefix))) {
            $names = $names->map(fn (string $name, int $index) => $index > 0 ? Str::after($name, $sharedPrefix) : $name);
        }

        return Arr::join($names->all(), ', ', $finalGlue);
    }
}

if (! function_exists('storefront_product_types')) {
    /**
     * e.g. "clothing, jewellery and bags" — plain words for descriptions and search hints.
     */
    function storefront_product_types(string $finalGlue = ' and '): string
    {
        $words = [
            'fashion' => 'clothing',
            'jewellery' => 'jewellery',
            'footwear' => 'footwear',
            'bags' => 'bags',
            'beauty-products' => 'beauty products',
            'home-products' => 'home products',
            'gift-items' => 'gifts',
        ];

        $types = storefront_collections()
            ->map(fn (array $collection) => $words[$collection['slug']] ?? Str::lower(str_replace('-', ' ', $collection['slug'])))
            ->unique()
            ->values();

        return $types->isEmpty() ? 'our collection' : Arr::join($types->all(), ', ', $finalGlue);
    }
}

if (! function_exists('mask_mobile')) {
    function mask_mobile(?string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $mobile) ?: '';

        if (strlen($mobile) < 4) {
            return '****';
        }

        return str_repeat('*', max(strlen($mobile) - 4, 0)).substr($mobile, -4);
    }
}

if (! function_exists('indian_states')) {
    function indian_states(): array
    {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
            'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
            'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
            'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];
    }
}
