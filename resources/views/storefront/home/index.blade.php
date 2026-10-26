@extends('layouts.storefront')

@php
    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => store_name(),
        'url' => url('/'),
    ]);
@endphp

@section('content')
@php
    $heroCta = filled($heroBanner?->button_text) ? $heroBanner->button_text : 'Shop the collection';
    $heroUrl = $heroBanner?->button_url
        ? (str_starts_with($heroBanner->button_url, 'http') ? $heroBanner->button_url : url($heroBanner->button_url))
        : route('shop');
@endphp
{{-- Store-owned product photos only — no stock banners / sliders. --}}
<section class="house-hero" aria-label="{{ store_name() }}">
    <div class="house-hero-stage">
        <div class="house-hero-panel">
            <p class="house-hero-brand">{{ store_name() }}</p>
            <h1 class="house-hero-title">What we stock is what you see.</h1>
            <p class="house-hero-sub house-hero-sub--full">{{ ucfirst(storefront_product_types()) }} from our shelves. Guest checkout · Cash on Delivery.</p>
            <p class="house-hero-sub house-hero-sub--short">From our shelves · COD available</p>
            <div class="house-hero-actions">
                <a href="{{ $heroUrl }}" class="btn btn-primary">{{ $heroCta }}</a>
                <a href="#collections" class="house-hero-link">Browse categories</a>
            </div>
        </div>

        @if($heroProducts->isNotEmpty())
            <div class="house-hero-mosaic house-hero-mosaic--{{ min(4, $heroProducts->count()) }}" aria-label="From our catalog">
                @foreach($heroProducts as $index => $product)
                    @php $heroImg = $product->displayImage(); @endphp
                    <a href="{{ $product->url() }}" class="house-hero-tile @if($index === 0) is-lead @endif" style="--tile-i: {{ $index }}">
                        <img
                            src="{{ $heroImg ? $heroImg->url('medium') : $product->thumbUrl() }}"
                            alt="{{ $product->name }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            @if($index === 0) fetchpriority="high" @endif
                        >
                        <span class="house-hero-tile-name">{{ $product->name }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if($sections->get('featured_categories')?->is_enabled !== false)
@php
    $homeSubs = $featuredSubcategories->isNotEmpty() ? $featuredSubcategories : $navSubcategories;
@endphp
<section id="collections" class="lookbook mt-10 md:mt-16 scroll-mt-24" aria-labelledby="lookbook-title">
    <div class="container-store">
        <div class="lookbook-head">
            <div>
                <p class="lookbook-kicker">Collections</p>
                <h2 id="lookbook-title" class="lookbook-title">{{ $sections->get('featured_categories')->title ?? 'Shop by category' }}</h2>
            </div>
        </div>

        <div class="lookbook-mosaic">
            @foreach($homeSubs as $index => $category)
                <a href="{{ $category->url() }}" class="lookbook-tile @if($index === 0) lookbook-tile-wide @endif" style="--tile-i: {{ $index }}">
                    <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" loading="lazy">
                    <span class="lookbook-tile-shade"></span>
                    <span class="lookbook-tile-label">
                        <span class="lookbook-tile-name">{{ $category->name }}</span>
                        <span class="lookbook-tile-cta">Shop</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($sections->get('featured_products')?->is_enabled !== false)
<section id="featured" class="container-store mt-14">
    <x-section-heading :title="$sections->get('featured_products')->title ?? 'Featured'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($featuredProducts as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
    @if($featuredProducts->hasPages())
        <div class="mt-8 flex justify-center">{{ $featuredProducts->links() }}</div>
    @endif
</section>
@endif

@if($sections->get('trending')?->is_enabled !== false)
<section class="container-store mt-8">
    <x-section-heading :title="$sections->get('trending')->title ?? 'Trending now'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($trendingProducts as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<div class="container-store"><x-ad position="home_middle" /></div>

@if($sections->get('bestsellers')?->is_enabled !== false)
<section class="container-store mt-8">
    <x-section-heading :title="$sections->get('bestsellers')->title ?? 'Best sellers'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($bestSellers as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

@if($sections->get('new_arrivals')?->is_enabled !== false)
<section class="container-store mt-14">
    <x-section-heading :title="$sections->get('new_arrivals')->title ?? 'New arrivals'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($newArrivals as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<section class="container-store mt-16 border-y border-line py-10">
    <div class="grid gap-8 md:grid-cols-3 md:gap-10">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold">Shipping</p>
            <p class="mt-2 font-serif text-2xl text-ink">Free shipping</p>
            <p class="mt-2 text-sm text-muted">On every order across India — no minimum cart value.</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold">Payment</p>
            <p class="mt-2 font-serif text-2xl text-ink">Cash on Delivery</p>
            <p class="mt-2 text-sm text-muted">Pay when your order arrives — no account needed.</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold">Returns</p>
            <p class="mt-2 font-serif text-2xl text-ink">7-day easy returns</p>
            <p class="mt-2 text-sm text-muted">On eligible products, as listed in our policy.</p>
        </div>
    </div>
</section>

<div class="container-store"><x-ad position="home_bottom" /></div>
@endsection
