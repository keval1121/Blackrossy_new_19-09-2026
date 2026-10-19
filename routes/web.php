<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\ContentAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/categories', [ShopController::class, 'categories'])->name('categories');
Route::get('/search', [ContentController::class, 'search'])->middleware('throttle:search')->name('search');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'coupon'])->name('cart.coupon');
Route::post('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/otp', [CheckoutController::class, 'otp'])->middleware('throttle:otp')->name('checkout.otp');
Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:checkout')->name('checkout.place');

Route::get('/order/success/{order:order_number}', [OrderController::class, 'success'])->name('order.success');
Route::get('/track-order', [OrderController::class, 'trackForm'])->name('track');
Route::post('/track-order', [OrderController::class, 'track'])->middleware('throttle:track')->name('track.submit');

Route::get('/blog/{any?}', [ContentController::class, 'gone'])->where('any', '.*')->name('blog.gone');
Route::get('/about', [ContentController::class, 'about'])->name('about');
Route::get('/faq', [ContentController::class, 'faq'])->name('faq');
Route::get('/policy', [ContentController::class, 'policy'])->name('policy');
Route::get('/contact', [ContentController::class, 'contact'])->name('contact');
Route::post('/contact', [ContentController::class, 'contactStore'])->middleware('throttle:contact')->name('contact.store');
Route::get('/sitemap.xml', [ContentController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [ContentController::class, 'robots'])->name('robots');
Route::get('/ads.txt', [ContentController::class, 'adsTxt'])->name('ads.txt');

Route::get('/{page:slug}', [ContentController::class, 'page'])
    ->where('page', 'privacy-policy|terms|shipping-policy|return-policy|refund-policy|cancellation-policy|cookie-policy')
    ->name('page.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:admin-login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('products/{product}/images/{image}/primary', [AdminProductController::class, 'makePrimaryImage'])->name('products.images.primary');
        Route::patch('products/{product}/stock', [AdminProductController::class, 'updateStock'])->name('products.stock');
        Route::patch('products/{product}/status', [AdminProductController::class, 'updateStatus'])->name('products.status');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::post('products/bulk-delete', [AdminProductController::class, 'bulkDelete'])->name('products.bulk');
        Route::get('products-export', [AdminProductController::class, 'export'])->name('products.export');
        Route::post('products-import', [AdminProductController::class, 'import'])->name('products.import');
        Route::patch('categories/{category}/active', [CategoryController::class, 'updateActive'])->name('categories.active');
        Route::patch('categories/{category}/homepage', [CategoryController::class, 'updateHomepage'])->name('categories.homepage');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
        Route::post('orders/bulk-status', [AdminOrderController::class, 'bulkStatus'])->name('orders.bulk-status');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/status', [AdminOrderController::class, 'status'])->name('orders.status');
        Route::get('orders/{order}/print', [AdminOrderController::class, 'print'])->name('orders.print');
        Route::get('banners', [CmsController::class, 'banners'])->name('banners.index');
        Route::post('banners/{banner?}', [CmsController::class, 'bannerSave'])->name('banners.save');
        Route::delete('banners/{banner}', [CmsController::class, 'bannerDelete'])->name('banners.delete');
        Route::get('homepage', [CmsController::class, 'homepage'])->name('homepage.index');
        Route::post('homepage/{section}', [CmsController::class, 'homepageSave'])->name('homepage.save');
        Route::get('ads', [CmsController::class, 'ads'])->name('ads.index');
        Route::post('ads/{ad}', [CmsController::class, 'adSave'])->name('ads.save');
        Route::get('pages', [ContentAdminController::class, 'pages'])->name('pages.index');
        Route::post('pages/{page}', [ContentAdminController::class, 'pageSave'])->name('pages.save');
        Route::get('settings', [ContentAdminController::class, 'settings'])->name('settings.index');
        Route::post('settings', [ContentAdminController::class, 'settingsSave'])->name('settings.save');
        Route::get('coupons', [ContentAdminController::class, 'coupons'])->name('coupons.index');
        Route::post('coupons/{coupon?}', [ContentAdminController::class, 'couponSave'])->name('coupons.save');
        Route::get('reviews', [ContentAdminController::class, 'reviews'])->name('reviews.index');
        Route::get('messages', [ContentAdminController::class, 'contacts'])->name('messages.index');
        Route::post('messages/{contactMessage}/read', [ContentAdminController::class, 'contactRead'])->name('messages.read');
        Route::delete('messages/{contactMessage}', [ContentAdminController::class, 'contactDelete'])->name('messages.delete');
        Route::post('reviews/{review}/approve', [ContentAdminController::class, 'reviewApprove'])->name('reviews.approve');
        Route::delete('reviews/{review}', [ContentAdminController::class, 'reviewDelete'])->name('reviews.delete');
        Route::get('brands', [ContentAdminController::class, 'brands'])->name('brands.index');
        Route::post('brands', [ContentAdminController::class, 'brandSave'])->name('brands.save');
        Route::delete('brands/{brand}', [ContentAdminController::class, 'brandDelete'])->name('brands.delete');
        Route::get('blocklist', [ContentAdminController::class, 'blocklist'])->name('blocklist.index');
        Route::post('blocklist', [ContentAdminController::class, 'blockSave'])->name('blocklist.save');
        Route::get('reports', [ContentAdminController::class, 'reports'])->name('reports.index');
    });
});

Route::get('/{category:slug}/{subcategory?}', [ShopController::class, 'category'])
    ->where('category', '^(?!admin$|shop$|search$|cart$|checkout$|blog$|about$|faq$|policy$|contact$|product$|track-order$|order$|categories$|sitemap\.xml$|robots\.txt$)[^/]+$')
    ->where('subcategory', '[^/]+')
    ->name('category.show');
