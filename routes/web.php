<?php

use App\Http\Controllers\AccessoryOrderController;
use App\Http\Controllers\Admin\AccessoryOrderController as AdminAccessoryOrderController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AiConversationController as AdminAiConversationController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ServiceBookingController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Ai\ShowroomAssistantController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Client\AppointmentController;
use App\Http\Controllers\Client\CustomerPortalLookupController;
use App\Http\Controllers\Client\GuestAppointmentManageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function (ShowroomCacheService $cacheService) {
    $homeData = $cacheService->homeData();

    return view('welcome', [
        'featuredProducts' => $homeData['featuredProducts'],
        'latestArticles' => $homeData['latestArticles'],
    ]);
})->name('home');

Route::get('/catalog', [ProductController::class, 'index'])->name('products.index');
Route::get('/catalog/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/compare', [ProductController::class, 'compare'])->name('products.compare');
Route::get('/tim-hieu-them', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/tim-hieu-them/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

// Accessories (reuse ProductController with accessory type filter)
Route::get('/accessories', [ProductController::class, 'index'])->defaults('type', 'accessory')->name('accessories.index');
Route::get('/accessories/{product:slug}/order', [AccessoryOrderController::class, 'create'])->name('accessory-orders.create');
Route::post('/accessories/{product:slug}/order', [AccessoryOrderController::class, 'store'])
    ->middleware('throttle:public-booking-submit')
    ->name('accessory-orders.store');

// Static Pages
Route::get('/offers', [PageController::class, 'exclusiveOffers'])->name('offers.exclusive');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('policy.privacy');
Route::get('/contact', [PageController::class, 'contact'])->name('contact.index');

// Customer Self-Service Lookup Portal (/tra-cuu)
Route::get('/tra-cuu', [CustomerPortalLookupController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('customer.lookup.index');
Route::post('/tra-cuu', [CustomerPortalLookupController::class, 'search'])
    ->middleware('throttle:20,1')
    ->name('customer.lookup.search');

// Public Booking Routes
Route::get('/booking', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/booking', [AppointmentController::class, 'store'])
    ->middleware('throttle:public-booking-submit')
    ->name('appointments.store');
Route::get('/booking/success', [AppointmentController::class, 'success'])->name('appointments.success');
Route::get('/appointments/{appointment}/manage', [GuestAppointmentManageController::class, 'show'])
    ->middleware('signed')
    ->name('appointments.guest.manage');
Route::post('/appointments/{appointment}/manage', [GuestAppointmentManageController::class, 'update'])
    ->middleware('signed')
    ->name('appointments.guest.update');

Route::post('/ai/showroom-assistant', ShowroomAssistantController::class)
    ->middleware('throttle:ai-concierge-chat')
    ->name('ai.showroom-assistant.ask');
Route::post('/ai/showroom-assistant/stream', [ShowroomAssistantController::class, 'stream'])
    ->middleware('throttle:ai-concierge-chat')
    ->name('ai.showroom-assistant.stream');
Route::get('/ai/showroom-assistant/history', [ShowroomAssistantController::class, 'history'])
    ->middleware('throttle:30,1')
    ->name('ai.showroom-assistant.history');
Route::post('/ai/showroom-assistant/reset', [ShowroomAssistantController::class, 'reset'])
    ->middleware('throttle:20,1')
    ->name('ai.showroom-assistant.reset');

Route::post('/api/ai/assistant', ShowroomAssistantController::class)
    ->middleware('throttle:ai-concierge-chat')
    ->name('ai.api.assistant.ask');
Route::post('/api/ai/assistant/stream', [ShowroomAssistantController::class, 'stream'])
    ->middleware('throttle:ai-concierge-chat')
    ->name('ai.api.assistant.stream');
Route::get('/api/ai/assistant/history', [ShowroomAssistantController::class, 'history'])
    ->middleware('throttle:30,1')
    ->name('ai.api.assistant.history');

// Auth Dashboard & Appointments Mng
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['admin', 'role:admin,sales'])
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
        // Content & Marketing Portal (admin + marketing)
        Route::middleware('role:admin,marketing')->group(function () {
            Route::resource('products', AdminProductController::class);
            Route::resource('categories', CategoryController::class);
            Route::post('articles/ai-assist', [AdminArticleController::class, 'aiAssist'])->name('articles.ai-assist');
            Route::resource('articles', AdminArticleController::class)->except(['show']);
            Route::post('customers/promotions', [CustomerController::class, 'sendPromotions'])->name('customers.promotions.store');
            Route::get('customers/purchased-suggestions', [CustomerController::class, 'purchasedSuggestions'])
                ->name('customers.purchased-suggestions');

            // Email Template Studio (admin + marketing only; sales receives 403)
            Route::prefix('email-templates')->name('email-templates.')->group(function () {
                Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
                Route::get('/create', [EmailTemplateController::class, 'create'])->name('create');
                Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
                Route::get('/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
                Route::put('/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('update');
                Route::post('/{emailTemplate}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('duplicate');
                Route::patch('/{emailTemplate}/status', [EmailTemplateController::class, 'updateStatus'])->name('status');
            });
        });

        // CRM & Sales Operations Portal (admin + sales)
        Route::middleware('role:admin,sales')->group(function () {
            Route::get('users/{user}/performance', [UserController::class, 'performance'])
                ->name('users.performance');
            Route::get('sales/{user}/performance', [UserController::class, 'performance'])
                ->name('sales.performance');
            Route::resource('appointments', App\Http\Controllers\Admin\AppointmentController::class)->only(['index', 'update']);
            Route::get('service-bookings', [ServiceBookingController::class, 'index'])
                ->name('service-bookings.index');
            Route::get('mail-preview/appointment/{appointment}', [App\Http\Controllers\Admin\AppointmentController::class, 'previewMail'])
                ->name('mail-preview.appointment');
            Route::get('accessory-orders', [AdminAccessoryOrderController::class, 'index'])->name('accessory-orders.index');
            Route::get('accessory-orders/{accessoryOrder}', [AdminAccessoryOrderController::class, 'show'])->name('accessory-orders.show');
            Route::patch('accessory-orders/{accessoryOrder}/status', [AdminAccessoryOrderController::class, 'updateStatus'])->name('accessory-orders.update-status');
            Route::get('mail-preview/accessory-order/{accessoryOrder}', [AdminAccessoryOrderController::class, 'previewMail'])
                ->name('mail-preview.accessory-order');
            Route::middleware('verified')->group(function () {
                Route::get('ai-conversations', [AdminAiConversationController::class, 'index'])->name('ai-conversations.index');
                Route::get('ai-conversations/{session}', [AdminAiConversationController::class, 'show'])->name('ai-conversations.show');
                Route::patch('ai-conversations/{session}/status', [AdminAiConversationController::class, 'updateStatus'])->name('ai-conversations.update-status');
            });
        });

        // Shared Customer Intelligence Read Access (admin + sales + marketing)
        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware('role:admin,sales,marketing')
            ->name('customers.index');

        // System & Staff Management (admin only — declared BEFORE wildcard users/{user} below)
        Route::middleware('role:admin')->group(function () {
            Route::post('customers/distribute-sales', [CustomerController::class, 'distributeSales'])->name('customers.distribute-sales');
            Route::get('site-settings', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
            Route::put('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
            Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        });

        // Customer 360° Detail, Installment Plans, Quick 1-to-1 Email & Staff Self/Admin Profile
        Route::middleware('role:admin,sales,marketing')->group(function () {
            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('staff-profile/{user}', [UserController::class, 'show'])->name('staff.profile');
            Route::get('customers/{phone}', [CustomerController::class, 'show'])->name('customers.show');
            Route::post('customers/{phone}/installments', [CustomerController::class, 'storeInstallment'])->name('customers.installments.store');
            Route::patch('customers/{phone}/installments/{plan}', [CustomerController::class, 'updateInstallment'])->name('customers.installments.update');
            Route::post('customers/{phone}/send-email', [CustomerController::class, 'sendQuickEmail'])->name('customers.send-email');
        });

        // Accounting & Financial Ledgers (strictly Top Super Admin only)
        Route::middleware('top_super_admin')->prefix('accounting')->name('accounting.')->group(function () {
            Route::get('/', [AccountingController::class, 'index'])->name('index');
            Route::get('/export', [AccountingController::class, 'exportCsv'])->name('export');
            Route::get('/days/{date}', [AccountingController::class, 'showDay'])->name('day');
            Route::post('/days/{date}/close', [AccountingController::class, 'closeDay'])->name('close-day');
        });
    });
});

require __DIR__.'/auth.php';
// Services & Experiences Routes
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/dich-vu', [ServiceController::class, 'index'])->name('services.dich-vu');
Route::get('/experiences', [ServiceController::class, 'experiences'])->name('experiences.index');

// API for cascading dropdowns
Route::get('/api/products-by-category', [ProductController::class, 'getProductsByCategory'])->name('api.products.category');
