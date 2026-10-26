<?php

return [
    'order_prefix' => env('SHOP_ORDER_PREFIX', 'ORD'),
    'order_start' => (int) env('SHOP_ORDER_START', 10001),
    'currency' => env('SHOP_CURRENCY', 'INR'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '₹'),
    'admin_email' => env('SHOP_ADMIN_EMAIL', 'admin@blackrossy.com'),
    'cart_cookie' => 'blackrossy_cart',
    'cart_cookie_days' => 30,
    'duplicate_order_minutes' => 15,
    'max_orders_per_ip_day' => 8,
    'max_orders_per_mobile_day' => 5,
    'otp_ttl_minutes' => 10,
    'low_stock_threshold' => 5,
    'related_limit' => 8,
    'listing_per_page' => 24,
    'search_suggestions' => 8,
    'image' => [
        'max_kb' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'thumb' => 400,
        'medium' => 800,
        'large' => 1400,
        'quality' => 82,
    ],
    'ad_positions' => [
        'home_middle' => 'Homepage — between Trending and Best sellers',
        'home_bottom' => 'Homepage — after the shipping & returns strip',
        'category_top' => 'Category / Shop — after intro text, before products',
        'listing_middle' => 'Category & search — after the 6th product',
        'product_bottom' => 'Product — after the description',
    ],
];
