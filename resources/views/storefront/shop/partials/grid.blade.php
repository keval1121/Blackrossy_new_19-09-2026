@if($products->isEmpty())
    <div class="rounded-3xl border border-dashed border-line bg-white px-6 py-16 text-center">
        <p class="font-serif text-2xl">No products found</p>
        <p class="mt-2 text-sm text-muted">Try another category or check back soon.</p>
        <a href="{{ url('/#collections') }}" class="btn btn-outline mt-6">Browse categories</a>
    </div>
@else
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        @foreach($products as $index => $product)
            <x-product-card :product="$product" />
            @if($index === 5 && ! $loop->last && ! request()->ajax() && ad_code('listing_middle'))
                <div class="col-span-2 lg:col-span-3"><x-ad position="listing_middle" /></div>
            @endif
        @endforeach
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
@endif
