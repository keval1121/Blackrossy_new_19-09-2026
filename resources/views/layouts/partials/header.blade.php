<header class="sticky top-0 z-40 border-b border-line bg-cream/95 backdrop-blur">
    <div class="container-store flex items-center gap-4 py-3">
        <a href="{{ route('home') }}" class="font-serif text-2xl tracking-tight text-ink md:text-4xl">{{ store_name() }}</a>
        <form action="{{ route('search') }}" method="get" class="relative hidden flex-1 md:block">
            <input id="live-search" name="q" value="{{ request('q') }}" placeholder="Search {{ storefront_product_types(', ') }}..." class="pl-4 pr-12" autocomplete="off">
            <div id="search-suggest" class="absolute z-50 mt-2 hidden w-full overflow-hidden rounded-2xl border border-line bg-white shadow-xl"></div>
        </form>
        <nav class="ml-auto hidden items-center gap-5 text-sm font-medium lg:flex">
            <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
            <div class="cat-nav" data-cat-nav>
                <a href="{{ route('categories') }}" class="cat-nav-trigger hover:text-gold">Categories</a>
                <div class="cat-nav-dropdown" data-cat-dropdown>
                    <div class="cat-menu">
                        @foreach($navSubcategories as $child)
                            <a href="{{ $child->url() }}" class="cat-menu-link">{{ $child->name }}</a>
                        @endforeach
                        <a href="{{ route('categories') }}" class="cat-menu-all">All categories →</a>
                    </div>
                </div>
            </div>
            <a href="{{ route('faq') }}" class="hover:text-gold">FAQ</a>
            <a href="{{ route('about') }}" class="hover:text-gold">About</a>
            <a href="{{ route('contact') }}" class="hover:text-gold">Contact</a>
            <a href="{{ route('policy') }}" class="hover:text-gold">Policy</a>
        </nav>
        <div class="ml-auto flex items-center gap-3 md:ml-0">
            <button class="md:hidden" data-toggle="search-modal" aria-label="Search">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="6"/><path d="M14 14l5 5"/></svg>
            </button>
            <a href="{{ route('cart.index') }}" class="relative" aria-label="Cart">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16l-1.5 11h-13z"/><path d="M8 6V5a3 3 0 016 0v1"/></svg>
                <span data-cart-count class="{{ $cartCount ? '' : 'hidden' }} absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
    <div class="border-t border-line md:hidden">
        <div class="container-store flex gap-4 overflow-x-auto py-2.5 text-sm">
            @foreach($navSubcategories as $child)
                @php
                    $childPath = trim(parse_url($child->url(), PHP_URL_PATH) ?: '', '/');
                    $isActive = $childPath !== '' && request()->is($childPath);
                @endphp
                <a
                    href="{{ $child->url() }}"
                    class="nav-chip whitespace-nowrap {{ $isActive ? 'is-active' : 'text-muted' }}"
                >{{ $child->name }}</a>
            @endforeach
        </div>
    </div>
</header>

<script>
(() => {
    const nav = document.querySelector('[data-cat-nav]');
    if (!nav) return;

    let closeTimer = null;
    const open = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        nav.classList.add('is-open');
    };
    const scheduleClose = () => {
        closeTimer = setTimeout(() => nav.classList.remove('is-open'), 220);
    };

    nav.addEventListener('mouseenter', open);
    nav.addEventListener('mouseleave', scheduleClose);
    nav.addEventListener('focusin', open);
    nav.addEventListener('focusout', (event) => {
        if (!nav.contains(event.relatedTarget)) {
            scheduleClose();
        }
    });
})();
</script>
