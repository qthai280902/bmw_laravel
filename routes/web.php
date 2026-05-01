<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\AppointmentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    $featuredProducts = Product::active()
        ->where('is_featured', true)
        ->with(['category', 'primaryImage'])
        ->latest()
        ->take(3)
        ->get();

    return view('welcome', compact('featuredProducts'));
})->name('home');

Route::get('/catalog', [ProductController::class, 'index'])->name('products.index');
Route::get('/catalog/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/compare', [ProductController::class, 'compare'])->name('products.compare');

// Accessories (reuse ProductController with accessory type filter)
Route::get('/accessories', [ProductController::class, 'index'])->defaults('type', 'accessory')->name('accessories.index');

// Static Pages
Route::get('/offers', [PageController::class, 'exclusiveOffers'])->name('offers.exclusive');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('policy.privacy');
Route::get('/contact', [PageController::class, 'contact'])->name('contact.index');

// Public Booking Routes
Route::get('/booking', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/booking', [AppointmentController::class, 'store'])->name('appointments.store');
Route::get('/booking/success', [AppointmentController::class, 'success'])->name('appointments.success');

// Auth Dashboard & Appointments Mng
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard redirect to appointments
    Route::redirect('/dashboard', '/appointments')->name('dashboard');
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

        Route::resource('appointments', App\Http\Controllers\Admin\AppointmentController::class)->only(['index', 'update']);
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
