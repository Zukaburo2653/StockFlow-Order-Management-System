<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebOrderController;
use App\Http\Controllers\WebProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ── Protected Web Routes ──────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/products',  [WebProductController::class, 'index'])->name('products.index');

    // Orders CRUD
    Route::get('/orders',              [WebOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create',       [WebOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders',             [WebOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}',      [WebOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [WebOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}',      [WebOrderController::class, 'update'])->name('orders.update');

    // ── Status Transitions (automatic — no manual status change) ──────────────
    // PENDING → PROCESSING
    Route::post('/orders/{order}/confirm',  [WebOrderController::class, 'confirm'])->name('orders.confirm');
    // PROCESSING → COMPLETED
    Route::post('/orders/{order}/complete', [WebOrderController::class, 'complete'])->name('orders.complete');
    // PENDING → CANCELLED (+ stock restore)
    Route::delete('/orders/{order}/cancel', [WebOrderController::class, 'cancel'])->name('orders.cancel');
});

//Hello mayur 