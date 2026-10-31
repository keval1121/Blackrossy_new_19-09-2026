@php
    $adsenseClient = adsense_client_id();
    $adsReady = (bool) ($adsenseClient && setting('adsense_enabled', false) && empty($hideAds));
    $gaId = ga_measurement_id();
@endphp
<div id="cookie-notice" class="fixed inset-x-0 bottom-20 z-50 hidden border-t border-line bg-cream/95 p-4 shadow-lg backdrop-blur md:bottom-4 md:left-4 md:right-auto md:max-w-md md:rounded-2xl md:border" role="dialog" aria-labelledby="cookie-notice-title" aria-live="polite">
    <p id="cookie-notice-title" class="text-sm font-semibold text-ink">Cookies &amp; ads choices</p>
    <p class="mt-2 text-sm leading-6 text-ink">
        We use cookies for cart and security so checkout works.
        @if ($adsReady)
            If you accept, cookies and personal data may also be shared with Google for advertising, including
            <strong>ads personalization</strong> and measuring ad performance.
            How Google uses information:
            <a class="underline" href="https://business.safety.google/privacy/" rel="noopener noreferrer" target="_blank">Google Business Data Responsibility</a>.
        @endif
        See our
        <a class="underline" href="{{ url('/privacy-policy') }}">Privacy Policy</a>
        and
        <a class="underline" href="{{ url('/cookie-policy') }}">Cookie Policy</a>.
        Manage personalised ads anytime:
        <a class="underline" href="https://adssettings.google.com" rel="noopener noreferrer" target="_blank">Google Ads Settings</a>.
    </p>
    @if ($adsReady)
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <button type="button" id="cookie-notice-essential" class="btn btn-outline w-full text-sm">Essential only</button>
            <button type="button" id="cookie-notice-accept" class="btn btn-primary w-full text-sm">Accept</button>
        </div>
    @else
        <button type="button" id="cookie-notice-accept" class="btn btn-primary mt-3 w-full text-sm">Got it</button>
    @endif
</div>
<script>
    (function () {
        var STORAGE_KEY = 'blackrossy_cookie_choice';
        var LEGACY_KEY = 'blackrossy_cookie_notice';
        var adsReady = @json($adsReady);
        var adsenseClient = @json($adsenseClient);
        var gaId = @json($gaId);
        var el = document.getElementById('cookie-notice');
        var acceptBtn = document.getElementById('cookie-notice-accept');
        var essentialBtn = document.getElementById('cookie-notice-essential');

        function choice() {
            try {
                var value = localStorage.getItem(STORAGE_KEY);
                if (value === 'accepted' || value === 'essential') {
                    return value;
                }
                // Old “Got it” banner — treat as essential only so ads do not auto-start.
                if (localStorage.getItem(LEGACY_KEY) === '1') {
                    return 'essential';
                }
            } catch (e) {}
            return null;
        }

        function save(value) {
            try {
                localStorage.setItem(STORAGE_KEY, value);
                localStorage.removeItem(LEGACY_KEY);
            } catch (e) {}
        }

        function loadAdsense() {
            @if ($adsReady)
            if (!adsenseClient || window.__brAdsenseLoaded) {
                return;
            }
            window.__brAdsenseLoaded = true;
            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(adsenseClient);
            script.crossOrigin = 'anonymous';
            document.head.appendChild(script);
            @endif
        }

        function loadAnalytics() {
            @if ($gaId)
            if (window.__brGaLoaded) {
                return;
            }
            window.__brGaLoaded = true;
            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
            document.head.appendChild(script);
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }
            window.gtag = gtag;
            gtag('js', new Date());
            gtag('config', gaId);
            @endif
        }

        function apply(value) {
            if (value === 'accepted') {
                loadAdsense();
                loadAnalytics();
            }
            if (el) {
                el.classList.add('hidden');
            }
        }

        var current = choice();
        if (current) {
            apply(current);
            return;
        }

        if (!el || !acceptBtn) {
            return;
        }

        el.classList.remove('hidden');
        acceptBtn.addEventListener('click', function () {
            save(adsReady ? 'accepted' : 'essential');
            apply(adsReady ? 'accepted' : 'essential');
            if (!adsReady) {
                loadAnalytics();
            }
        });
        if (essentialBtn) {
            essentialBtn.addEventListener('click', function () {
                save('essential');
                apply('essential');
            });
        }
    })();
</script>
