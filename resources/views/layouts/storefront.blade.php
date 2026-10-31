<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle ??= setting('seo_title') ?: store_name().' | '.storefront_collection_names(' & ');
        $seoDescription ??= setting('seo_description') ?: 'Shop '.storefront_product_types().' at '.store_name().' with guest checkout and Cash on Delivery across India.';
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @if(setting('favicon'))
        <link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">
    @endif
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @isset($schema)
        <script type="application/ld+json">{!! $schema !!}</script>
    @endisset
    @if ($adsenseClient = adsense_client_id())
        <meta name="google-adsense-account" content="{{ $adsenseClient }}">
    @endif
    @if ($adsenseClient && setting('adsense_enabled', false) && empty($hideAds))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseClient }}" crossorigin="anonymous"></script>
    @endif
    @if ($gaId = ga_measurement_id())
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif
</head>
<body class="bg-sand text-ink antialiased pb-24 md:pb-0">
    <div id="toast-wrap" class="toast-wrap"></div>
    @include('layouts.partials.header')
    <main>
        @if(session('status'))
            <div class="container-store pt-4">
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
            </div>
        @endif
        @if($errors->any())
            <div class="container-store pt-4">
                <p class="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</p>
            </div>
        @endif
        @yield('content')
    </main>
    @include('layouts.partials.footer')
    @include('layouts.partials.bottom-nav')
    @include('layouts.partials.search-modal')
</body>
</html>
