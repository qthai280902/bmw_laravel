<?php

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Client\AppointmentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/catalog', [ProductController::class, 'index'])->name('products.index');
Route::get('/catalog/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/compare', [ProductController::class, 'compare'])->name('products.compare');

// Cart Routes (Guest + Auth)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::delete('/cart/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');

// Checkout Routes (Auth Only)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/{order}/success', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Auth Dashboard, Orders & Appointments
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Client\OrderController::class, 'index'])->name('dashboard');
    Route::get('/orders/{order}', [App\Http\Controllers\Client\OrderController::class, 'show'])->name('orders.show');

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', AdminProductController::class);
        Route::resource('orders', OrderController::class)->only(['index', 'show', 'update']);
        Route::resource('appointments', App\Http\Controllers\Admin\AppointmentController::class)->only(['index', 'update']);
        Route::resource('users', UserController::class)->only(['index']);
    });
});

require __DIR__.'/auth.php';
