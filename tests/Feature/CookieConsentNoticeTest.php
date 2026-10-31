<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_does_not_show_cookie_popup(): void
    {
        Setting::put('adsense_client_id', 'ca-pub-1983284873156439', 'store');
        Setting::put('adsense_enabled', true, 'store');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="cookie-notice"', false);
        $response->assertDontSee('>Got it</button>', false);
        $response->assertDontSee('>Essential only</button>', false);
        $response->assertDontSee('Cookies &amp; ads choices', false);
    }
}
