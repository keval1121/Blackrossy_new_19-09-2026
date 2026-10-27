@extends('layouts.storefront')

@php
    $seoTitle = $product->seo_title ?: $product->name.' | '.store_name();
    $seoDescription = $product->seo_description
        ?: ($product->short_description
            ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160));
    $ogImage = $product->displayImage()?->url('large');
    $ogType = 'product';
    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $product->images->map->url('large')->all(),
        'description' => strip_tags((string) ($product->short_description ?: $product->description)),
        'sku' => $product->sku,
        'brand' => $product->brand?->name,
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'INR',
            'price' => $product->selling_price,
            'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->url(),
        ],
    ]);
@endphp

@section('content')
<div class="container-store product-detail grid gap-6 py-6 md:grid-cols-2 md:gap-10">
    <div class="product-detail-gallery">
        @php
            $galleryImages = $product->images->values();
            $galleryMain = $product->displayImage();
            $galleryStart = 0;
            if ($galleryMain) {
                $foundIndex = $galleryImages->search(fn ($image) => $image->id === $galleryMain->id);
                $galleryStart = $foundIndex === false ? 0 : (int) $foundIndex;
            }
            $galleryUrls = $galleryImages->map(fn ($image) => $image->url('large'))->values()->all();
            if ($galleryUrls === []) {
                $galleryUrls = [$product->thumbUrl()];
            }
        @endphp
        <div class="product-gallery" data-product-gallery data-start="{{ $galleryStart }}">
            <div class="product-gallery-stage">
                <img
                    id="main-image"
                    class="product-gallery-image"
                    src="{{ $galleryUrls[$galleryStart] ?? $galleryUrls[0] }}"
                    alt="{{ $product->name }}"
                    data-gallery-image
                >
                @if(count($galleryUrls) > 1)
                    <button type="button" class="product-gallery-nav product-gallery-nav-prev" data-gallery-prev aria-label="Previous image">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="product-gallery-nav product-gallery-nav-next" data-gallery-next aria-label="Next image">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                @endif
            </div>
            <script type="application/json" data-gallery-sources>{!! json_encode($galleryUrls, JSON_UNESCAPED_SLASHES) !!}</script>
        </div>
    </div>
    <div class="product-detail-info">
        <h1 class="font-serif text-3xl leading-tight md:text-5xl">{{ $product->name }}</h1>
        <div class="mt-4 flex items-end gap-3">
            <p id="price" class="text-3xl font-semibold">{{ money($product->selling_price) }}</p>
            <p id="mrp" class="price-mrp">{{ money($product->mrp) }}</p>
            @if($product->discountPercent())
                <span class="badge-off">{{ $product->discountPercent() }}% OFF</span>
            @endif
        </div>
        <p id="stock-label" class="stock-label {{ $product->inStock() ? 'is-in' : 'is-out' }}">{{ $product->inStock() ? 'In stock' : 'Out of stock' }}</p>
        @if(filled($product->short_description))
            <p class="mt-4 text-sm leading-6 text-muted">{{ $product->short_description }}</p>
        @endif

        <form data-ajax-cart action="{{ route('cart.add') }}" method="post" class="mt-6 space-y-5" id="buy-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="variant_id" id="variant_id" value="{{ $product->activeVariants->first()?->id }}">
            @foreach($attributes as $group)
                <div>
                    <p class="mb-2 text-sm font-semibold">{{ $group['attribute']->name }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($group['values'] as $index => $value)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="attr_{{ $group['attribute']->id }}" value="{{ $value->id }}" class="peer sr-only variant-attr" data-attr="{{ $group['attribute']->id }}" @checked($index===0)>
                                <span class="block rounded-full border border-line px-4 py-2 text-sm peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white">{{ $value->value }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div>
                <p class="mb-2 text-sm font-semibold">Quantity</p>
                <div class="qty-stepper" data-qty-stepper>
                    <button type="button" class="qty-stepper-btn" data-qty-minus aria-label="Decrease quantity">−</button>
                    <input type="number" name="quantity" value="1" min="1" inputmode="numeric" class="qty-stepper-input" data-qty-input>
                    <button type="button" class="qty-stepper-btn" data-qty-plus aria-label="Increase quantity">+</button>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <button class="btn btn-outline w-full" type="submit" onclick="this.form.buy_now.value=0">Add to cart</button>
                <button class="btn btn-gold w-full" type="submit" onclick="this.form.buy_now.value=1">Buy now</button>
            </div>
            <input type="hidden" name="buy_now" value="0">
            <p class="text-sm text-emerald-700">Cash on Delivery available</p>
        </form>
    </div>
</div>

<div class="container-store mt-10">
    <article class="rounded-3xl bg-white p-6">
        <h2 class="font-serif text-3xl">Description</h2>
        <div class="prose mt-4 max-w-none text-sm leading-7">{!! nl2br(e($product->description)) !!}</div>
        @if($product->specifications)
            <h3 class="mt-8 font-semibold">Specifications</h3>
            <dl class="mt-3 divide-y divide-line text-sm">
                @foreach($product->specifications as $spec)
                    <div class="flex justify-between gap-4 py-2"><dt class="text-muted">{{ $spec['label'] }}</dt><dd>{{ $spec['value'] }}</dd></div>
                @endforeach
            </dl>
        @endif
        <p class="mt-6 text-sm text-muted">{{ $product->shipping_info ?: 'Ships in 2-4 days. Free shipping on every order.' }}</p>
        <p class="mt-2 text-sm text-muted">{{ $product->return_info ?: '7-day easy returns on unused products with tags intact.' }}</p>
    </article>
    <x-ad position="product_bottom" />
</div>

@if($related->isNotEmpty())
<section id="related" class="container-store mt-12 scroll-mt-24">
    <x-section-heading title="You may also like" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($related as $item)
            <x-product-card :product="$item" />
        @endforeach
    </div>
</section>
@endif

<script>
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const sourcesNode = gallery.querySelector('[data-gallery-sources]');
        const image = gallery.querySelector('[data-gallery-image]');
        const sources = sourcesNode ? JSON.parse(sourcesNode.textContent || '[]') : [];
        if (!image || sources.length < 2) {
            return;
        }

        let index = Number(gallery.dataset.start || 0);
        if (index < 0 || index >= sources.length) {
            index = 0;
        }

        const show = (nextIndex) => {
            index = (nextIndex + sources.length) % sources.length;
            image.src = sources[index];
        };

        gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => show(index - 1));
        gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(index + 1));
    });

    const variants = @json($variantMap);
    const stockLabel = document.getElementById('stock-label');
    const setStockLabel = (inStock) => {
        if (!stockLabel) {
            return;
        }
        stockLabel.textContent = inStock ? 'In stock' : 'Out of stock';
        stockLabel.classList.toggle('is-in', inStock);
        stockLabel.classList.toggle('is-out', !inStock);
    };
    const matchVariant = () => {
        const selected = {};
        document.querySelectorAll('.variant-attr:checked').forEach((el) => selected[el.dataset.attr] = Number(el.value));
        const found = variants.find((variant) => Object.keys(selected).every((key) => Number(variant.attrs[key]) === selected[key]));
        const id = document.getElementById('variant_id');
        if (found && id) {
            id.value = found.id;
            document.getElementById('price').textContent = new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR', maximumFractionDigits:0}).format(found.price);
            document.getElementById('mrp').textContent = new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR', maximumFractionDigits:0}).format(found.mrp);
            setStockLabel(found.stock > 0);
        }
    };
    document.querySelectorAll('.variant-attr').forEach((el) => el.addEventListener('change', matchVariant));
    matchVariant();

    document.querySelectorAll('[data-qty-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('[data-qty-input]');
        const minus = stepper.querySelector('[data-qty-minus]');
        const plus = stepper.querySelector('[data-qty-plus]');
        if (!input || !minus || !plus) {
            return;
        }
        minus.addEventListener('click', () => {
            input.value = String(Math.max(1, Number(input.value || 1) - 1));
        });
        plus.addEventListener('click', () => {
            input.value = String(Math.max(1, Number(input.value || 1) + 1));
        });
        input.addEventListener('change', () => {
            input.value = String(Math.max(1, Number(input.value || 1)));
        });
    });
</script>
@endsection
