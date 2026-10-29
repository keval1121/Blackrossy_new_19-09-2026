<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncStockedPolicyCopyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_jewellery_and_footwear_sell_claims(): void
    {
        Page::query()->create([
            'title' => 'Terms',
            'slug' => 'terms',
            'content' => 'Black Rossy is an online retail store based in India selling clothing, jewellery, footwear, bags, beauty, home and gift products.',
            'is_active' => true,
        ]);
        Page::query()->create([
            'title' => 'Return Policy',
            'slug' => 'return-policy',
            'content' => 'Eligible: Clothing, bags, unworn footwear, unused jewellery (with packaging) and unused home items.',
            'is_active' => true,
        ]);
        Page::query()->create([
            'title' => 'FAQ',
            'slug' => 'faq',
            'content' => 'Fashion jewellery and intimate items may have special rules.',
            'is_active' => true,
        ]);

        Artisan::call('shop:sync-stocked-policy-copy');

        $terms = (string) Page::query()->where('slug', 'terms')->value('content');
        $returns = (string) Page::query()->where('slug', 'return-policy')->value('content');
        $faq = (string) Page::query()->where('slug', 'faq')->value('content');

        $this->assertStringContainsString('selling clothing and bags', $terms);
        $this->assertStringNotContainsString('jewellery', strtolower($terms));
        $this->assertStringNotContainsString('footwear', strtolower($terms));

        $this->assertStringContainsString('Clothing and bags', $returns);
        $this->assertStringNotContainsString('jewellery', strtolower($returns));
        $this->assertStringNotContainsString('footwear', strtolower($returns));

        $this->assertStringNotContainsString('jewellery', strtolower($faq));
    }
}
