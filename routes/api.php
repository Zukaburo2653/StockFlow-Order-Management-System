<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API route NAMES are prefixed with 'api.' using the ->name() group.
| This prevents collision with web route names (orders.index, etc.)
| registered in web.php.
|
| Without the name prefix, Route::apiResource('orders') registers
| 'orders.index', 'orders.show', etc. — the same names as web.php —
| and route('orders.index') in blade resolves to /api/v1/orders (JSON)
| instead of /orders (HTML page).
|
| API URLs remain unchanged:
|   GET    /api/v1/products
|   GET    /api/v1/products/{product}
|   POST   /api/v1/orders        (auth:sanctum)
|   GET    /api/v1/orders        (auth:sanctum)
|   GET    /api/v1/orders/{order}(auth:sanctum)
|   PUT    /api/v1/orders/{order}(auth:sanctum)
|   DELETE /api/v1/orders/{order}(auth:sanctum)
|
*/

Route::prefix('v1')->name('api.')->group(function () {

    // ── Products (public) ─────────────────────────────────────────────────────
    Route::apiResource('products', ProductController::class)
        ->only(['index', 'show']);

    // ── Orders (Sanctum protected) ────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('orders', OrderController::class);
    });

});