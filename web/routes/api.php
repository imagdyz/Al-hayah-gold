<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/prices', [Api\PriceController::class, 'index'])->name('prices');
    Route::get('/prices/history', [Api\PriceController::class, 'history'])->name('prices.history');
    Route::get('/products', [Api\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [Api\ProductController::class, 'show'])->name('products.show');
    Route::get('/branches', [Api\BranchController::class, 'index'])->name('branches');

    Route::post('/auth/otp', [Api\AuthController::class, 'send'])->middleware('throttle:6,1')->name('auth.otp');
    Route::post('/auth/verify', [Api\AuthController::class, 'verify'])->middleware('throttle:10,1')->name('auth.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [Api\AuthController::class, 'me'])->name('me');
        Route::get('/orders', [Api\OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [Api\OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [Api\OrderController::class, 'show'])->name('orders.show');
    });
});
