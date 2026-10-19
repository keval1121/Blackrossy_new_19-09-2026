@extends('layouts.storefront')

@php
    $listingCategory = $subcategory ?? $category;
    $seoTitle = $listingCategory?->seo_title ?: $heading.' | '.store_name();
    $seoDescription = $listingCategory?->seo_description ?: ($listingCategory
        ? $heading.' at '.store_name().' — free shipping on every order and Cash on Delivery across India.'
        : 'Browse all '.storefront_product_types().' at '.store_name().' — free shipping on every order and Cash on Delivery across India.');
    $activeFilterCount = collect([
        request('min_price'),
        request('max_price'),
        ...(array) request('brand', []),
    ])->filter(fn ($v) => filled($v))->count()
        + collect((array) request('attr', []))->flatten()->filter()->count();
@endphp

@section('content')
<div class="container-store py-5 md:py-8">
    <nav class="mb-3 text-xs text-muted">
        <a href="{{ route('home') }}">Home</a>
        @if($category) / <a href="{{ $category->url() }}">{{ $category->name }}</a> @endif
        @if($subcategory) / {{ $subcategory->name }} @endif
    </nav>

    <div class="mb-4 flex items-end justify-between gap-3">
        <div class="max-w-3xl">
            <h1 class="font-serif text-3xl md:text-4xl">{{ $heading }}</h1>
            @if($listingCategory?->description)
                <p class="mt-3 text-sm leading-6 text-muted md:text-[15px]">{{ $listingCategory->description }}</p>
            @endif
        </div>
    </div>

    {{-- Mobile toolbar: filters stay closed until tap --}}
    <div class="mb-4 flex gap-2 md:hidden">
        <button type="button" id="open-filters" class="filter-trigger btn btn-outline flex-1 gap-2 text-sm">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
            Filter
            @if($activeFilterCount)
                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-ink text-[10px] text-white">{{ $activeFilterCount }}</span>
            @endif
        </button>
        <form method="get" class="flex-1" id="mobile-sort-form">
            @foreach(request()->except('sort', 'page') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $vv)
                                <input type="hidden" name="{{ $key }}[{{ $k }}][]" value="{{ $vv }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endif
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <select name="sort" class="h-12 rounded-full border border-line bg-white px-4 text-sm" onchange="this.form.submit()">
                <option value="popular" @selected(request('sort', 'popular')==='popular')>Popular</option>
                <option value="newest" @selected(request('sort')==='newest')>Newest</option>
                <option value="price_asc" @selected(request('sort')==='price_asc')>Price ↑</option>
                <option value="price_desc" @selected(request('sort')==='price_desc')>Price ↓</option>
                <option value="discount" @selected(request('sort')==='discount')>Discount</option>
            </select>
        </form>
    </div>

    <div class="md:grid md:grid-cols-[280px_1fr] md:gap-8">
        {{-- Backdrop (mobile only) --}}
        <div id="filter-backdrop" class="filter-backdrop"></div>

        {{-- Filter panel: drawer on mobile, sidebar on desktop --}}
        <aside id="filter-panel" class="filter-panel" aria-hidden="true">
            <div class="filter-panel-inner">
                <div class="flex items-center justify-between border-b border-line px-5 py-4 md:hidden">
                    <p class="text-base font-semibold">Filters</p>
                    <button type="button" id="close-filters" class="flex h-10 w-10 items-center justify-center rounded-full bg-sand" aria-label="Close filters">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <form id="filter-form" class="filter-form">
                    <input type="hidden" name="q" value="{{ request('q') }}">

                    <div class="hidden md:block">
                        <p class="filter-label">Sort</p>
                        <select name="sort" class="filter-select">
                            <option value="popular" @selected(request('sort', 'popular')==='popular')>Popular</option>
                            <option value="newest" @selected(request('sort')==='newest')>Newest</option>
                            <option value="price_asc" @selected(request('sort')==='price_asc')>Price: Low to High</option>
                            <option value="price_desc" @selected(request('sort')==='price_desc')>Price: High to Low</option>
                            <option value="discount" @selected(request('sort')==='discount')>Discount</option>
                        </select>
                    </div>

                    @if($filters['children']->isNotEmpty())
                        <div>
                            <p class="filter-label">Category</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($filters['children'] as $child)
                                    <a href="{{ $child->url() }}"
                                       class="filter-chip {{ optional($subcategory)->id === $child->id ? 'is-active' : '' }}">
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <p class="filter-label">Price</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min ₹" class="filter-input" inputmode="numeric">
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max ₹" class="filter-input" inputmode="numeric">
                        </div>
                    </div>

                    @if($filters['brands']->isNotEmpty())
                        <div>
                            <p class="filter-label">Brand</p>
                            <div class="space-y-1">
                                @foreach($filters['brands'] as $brand)
                                    <label class="filter-check">
                                        <input type="checkbox" name="brand[]" value="{{ $brand->id }}" @checked(in_array($brand->id, array_map('intval', (array) request('brand', []))))>
                                        <span>{{ $brand->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach($filters['attributes'] as $group)
                        <div>
                            <p class="filter-label">{{ $group->name }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($group->values as $value)
                                    @php($checked = in_array($value->id, array_map('intval', (array) data_get(request('attr'), $group->id, []))))
                                    <label class="filter-chip has-check {{ $checked ? 'is-active' : '' }}">
                                        <input type="checkbox" class="sr-only" name="attr[{{ $group->id }}][]" value="{{ $value->id }}" @checked($checked)>
                                        {{ $value->value }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="filter-actions">
                        <a href="{{ url()->current() }}" class="btn btn-outline flex-1 text-sm">Clear</a>
                        <button type="submit" class="btn btn-primary flex-1 text-sm" id="apply-filters-btn">Apply</button>
                    </div>
                </form>
            </div>
        </aside>

        <div>
            @if($products->isNotEmpty())
                <div class="hidden md:mb-2 md:block">
                    <x-ad position="category_top" />
                </div>
            @endif
            <div id="product-grid">
                @include('storefront.shop.partials.grid')
            </div>

            @if($listingCategory?->seo_content)
                <section class="mt-10 rounded-2xl border border-line bg-white px-5 py-6 md:px-7 md:py-8">
                    <h2 class="font-serif text-2xl text-ink">About {{ $heading }}</h2>
                    <div class="prose mt-4 max-w-none text-sm leading-7 text-stone-600">
                        {!! nl2br(e($listingCategory->seo_content)) !!}
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
