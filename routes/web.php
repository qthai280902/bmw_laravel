<?php

use App\Http\Controllers\AccessoryOrderController;
use App\Http\Controllers\Admin\AccessoryOrderController as AdminAccessoryOrderController;
use App\Http\Controllers\Admin\AiConversationController as AdminAiConversationController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Ai\ShowroomAssistantController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Client\AppointmentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Models\Article;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    $featuredProducts = Product::active()
        ->where('is_featured', true)
        ->with(['category', 'primaryImage', 'images'])
        ->latest()
        ->take(3)
        ->get();

    $latestArticles = Article::published()
        ->latest('published_at')
        ->take(3)
        ->get();

    return view('welcome', compact('featuredProducts', 'latestArticles'));
})->name('home');

Route::get('/catalog', [ProductController::class, 'index'])->name('products.index');
Route::get('/catalog/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/compare', [ProductController::class, 'compare'])->name('products.compare');
Route::get('/tim-hieu-them', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/tim-hieu-them/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

// Accessories (reuse ProductController with accessory type filter)
Route::get('/accessories', [ProductController::class, 'index'])->defaults('type', 'accessory')->name('accessories.index');
Route::get('/accessories/{product:slug}/order', [AccessoryOrderController::class, 'create'])->name('accessory-orders.create');
Route::post('/accessories/{product:slug}/order', [AccessoryOrderController::class, 'store'])->name('accessory-orders.store');

// Static Pages
Route::get('/offers', [PageController::class, 'exclusiveOffers'])->name('offers.exclusive');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('policy.privacy');
Route::get('/contact', [PageController::class, 'contact'])->name('contact.index');

// Public Booking Routes
Route::get('/booking', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/booking', [AppointmentController::class, 'store'])->name('appointments.store');
Route::get('/booking/success', [AppointmentController::class, 'success'])->name('appointments.success');

Route::post('/ai/showroom-assistant', ShowroomAssistantController::class)
    ->middleware('throttle:12,1')
    ->name('ai.showroom-assistant.ask');

// Auth Dashboard & Appointments Mng
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('admin')
        ->name('dashboard');
    // Appointments list
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', AdminProductController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('articles', AdminArticleController::class)->except(['show']);

        Route::resource('appointments', App\Http\Controllers\Admin\AppointmentController::class)->only(['index', 'update']);
        Route::get('accessory-orders', [AdminAccessoryOrderController::class, 'index'])->name('accessory-orders.index');
        Route::get('accessory-orders/{accessoryOrder}', [AdminAccessoryOrderController::class, 'show'])->name('accessory-orders.show');
        Route::patch('accessory-orders/{accessoryOrder}/status', [AdminAccessoryOrderController::class, 'updateStatus'])->name('accessory-orders.update-status');
        Route::middleware('verified')->group(function () {
            Route::get('ai-conversations', [AdminAiConversationController::class, 'index'])->name('ai-conversations.index');
            Route::get('ai-conversations/{session}', [AdminAiConversationController::class, 'show'])->name('ai-conversations.show');
            Route::patch('ai-conversations/{session}/status', [AdminAiConversationController::class, 'updateStatus'])->name('ai-conversations.update-status');
        });
        Route::get('site-settings', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
        Route::put('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');
        Route::resource('users', UserController::class)->only(['index']);
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    });
});

require __DIR__.'/auth.php';
// Services & Experiences Routes
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/experiences', [ServiceController::class, 'experiences'])->name('experiences.index');

// API for cascading dropdowns
Route::get('/api/products-by-category', [ProductController::class, 'getProductsByCategory'])->name('api.products.category');
