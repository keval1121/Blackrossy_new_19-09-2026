<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ads_enabled_banner_offers_accept_and_essential_with_google_disclosure(): void
    {
        Setting::put('adsense_client_id', 'ca-pub-1983284873156439', 'store');
        Setting::put('adsense_enabled', true, 'store');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('ads personalization', false);
        $response->assertSee('https://business.safety.google/privacy/', false);
        $response->assertSee('>Accept</button>', false);
        $response->assertSee('>Essential only</button>', false);
        $response->assertSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false);
        $response->assertDontSee('<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false);
    }

    public function test_ads_disabled_banner_stays_simple_and_skips_loader(): void
    {
        Setting::put('adsense_client_id', 'ca-pub-1983284873156439', 'store');
        Setting::put('adsense_enabled', false, 'store');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('>Got it</button>', false);
        $response->assertDontSee('>Essential only</button>', false);
        $response->assertDontSee('pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', false);
    }
}
