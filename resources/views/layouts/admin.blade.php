<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ store_name() }} Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-stone-800 antialiased">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="block text-2xl font-semibold tracking-tight text-white">{{ store_name() }}</a>
        <p class="mt-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-amber-500">Admin</p>
        <nav class="admin-sidebar-nav">
            @foreach([
                'admin.dashboard' => 'Dashboard',
                'admin.orders.index' => 'Orders',
                'admin.products.index' => 'Products',
                'admin.categories.index' => 'Categories',
                'admin.brands.index' => 'Brands',
                'admin.banners.index' => 'Banners',
                'admin.homepage.index' => 'Homepage',
                'admin.ads.index' => 'Ads',
                'admin.pages.index' => 'Pages',
                'admin.coupons.index' => 'Coupons',
                'admin.reviews.index' => 'Reviews',
                'admin.messages.index' => 'Queries',
                'admin.reports.index' => 'Reports',
                'admin.blocklist.index' => 'Blocklist',
                'admin.settings.index' => 'Settings',
            ] as $route => $label)
                <a href="{{ route($route) }}" class="admin-nav-link {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="post" action="{{ route('admin.logout') }}" class="mt-10">
            @csrf
            <button class="text-sm text-stone-500 hover:text-stone-300">Log out</button>
        </form>
    </aside>
    <div class="min-w-0">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-stone-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6">
            <h1 class="text-base font-semibold text-stone-900 sm:text-lg">@yield('title', 'Dashboard')</h1>
            <a href="{{ route('home') }}" class="admin-btn admin-btn-ghost text-amber-800" target="_blank" rel="noopener">View store</a>
        </header>
        <main class="p-4 sm:p-6">
            @if(session('status'))
                <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
