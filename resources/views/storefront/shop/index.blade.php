@extends('layouts.storefront')

@php
    $listingCategory = $subcategory ?? $category;
    $seoTitle = $listingCategory?->seo_title ?: $heading.' | '.store_name();
    $seoDescription = $listingCategory?->seo_description ?: ($listingCategory
        ? $heading.' at '.store_name().' — free shipping on every order and Cash on Delivery across India.'
        : 'Browse all '.storefront_product_types().' at '.store_name().' — free shipping on every order and Cash on Delivery across India.');
    $childCategories = $filters['children'] ?? collect();
    $productCount = $products->total();
@endphp

@section('content')
<div class="container-store py-4 md:py-8">
    <div class="mb-4">
        <h1 class="font-serif text-3xl md:text-4xl">{{ $heading }}</h1>
        @if($listingCategory?->description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted">{{ $listingCategory->description }}</p>
        @endif
    </div>

    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="min-w-0 text-xs text-stone-500">
            {{ $productCount }} {{ \Illuminate\Support\Str::plural('product', $productCount) }}
            <span class="shipping-meta"> · Free shipping · COD</span>
        </p>

        <form method="get" class="shrink-0">
            @foreach(request()->except('sort', 'page', 'min_price', 'max_price', 'brand', 'attr') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="sr-only" for="listing-sort">Sort</label>
            <select
                id="listing-sort"
                name="sort"
                class="listing-sort"
                onchange="this.form.submit()"
            >
                <option value="popular" @selected(request('sort', 'popular')==='popular')>Popular</option>
                <option value="newest" @selected(request('sort')==='newest')>Newest</option>
                <option value="price_asc" @selected(request('sort')==='price_asc')>Price ↑</option>
                <option value="price_desc" @selected(request('sort')==='price_desc')>Price ↓</option>
                <option value="discount" @selected(request('sort')==='discount')>Discount</option>
            </select>
        </form>
    </div>

    @if($childCategories->isNotEmpty() && ! $subcategory)
        <div class="mb-4 flex gap-2 overflow-x-auto">
            @foreach($childCategories as $child)
                <a href="{{ $child->url() }}" class="shrink-0 rounded-full border border-line bg-white px-3 py-1.5 text-sm">
                    {{ $child->name }}
                </a>
            @endforeach
        </div>
    @endif

    @if($products->isNotEmpty())
        <x-ad position="category_top" />
    @endif

    <div id="product-grid">
        @include('storefront.shop.partials.grid')
    </div>

    @if($listingCategory?->seo_content)
        <section class="listing-about mt-8 border-t border-line pt-6">
            <h2 class="font-serif text-xl text-ink">About {{ $heading }}</h2>
            <div class="prose mt-3 max-w-none text-sm leading-7 text-stone-600">
                {!! nl2br(e($listingCategory->seo_content)) !!}
            </div>
        </section>
    @endif
</div>
@endsection
