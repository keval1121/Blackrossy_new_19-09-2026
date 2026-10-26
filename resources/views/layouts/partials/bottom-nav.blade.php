<nav class="bottom-nav fixed inset-x-0 bottom-0 z-40 border-t border-line bg-cream md:hidden">
    <div class="grid grid-cols-5 text-[11px]">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-1 py-3 {{ request()->routeIs('home') ? 'text-gold' : '' }}">🏠<span>Home</span></a>
        <a href="{{ url('/#collections') }}" class="flex flex-col items-center gap-1 py-3">📂<span>Categories</span></a>
        <button data-toggle="search-modal" class="flex flex-col items-center gap-1 py-3">🔍<span>Search</span></button>
        <a href="{{ route('cart.index') }}" class="relative flex flex-col items-center gap-1 py-3">🛒<span>Cart</span>
            <span data-cart-count class="{{ $cartCount ? '' : 'hidden' }} absolute right-5 top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-gold text-[9px] text-white">{{ $cartCount }}</span>
        </a>
        <a href="{{ route('track') }}" class="flex flex-col items-center gap-1 py-3">📦<span>Orders</span></a>
    </div>
</nav>
