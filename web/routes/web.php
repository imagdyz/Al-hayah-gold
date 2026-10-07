<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

Route::get('/', [Site\HomeController::class, 'index'])->name('home');
Route::get('/prices', [Site\PriceController::class, 'index'])->name('prices');
Route::get('/bullion', [Site\BullionController::class, 'index'])->name('bullion');
Route::get('/jewelry', [Site\ShopController::class, 'index'])->name('shop.index');
Route::get('/jewelry/{product}', [Site\ShopController::class, 'show'])->name('shop.show');
Route::get('/sell', [Site\SellController::class, 'create'])->name('sell');
Route::get('/branches', [Site\BranchController::class, 'index'])->name('branches');
Route::get('/p/{page}', [Site\PageController::class, 'show'])->whereIn('page', ['faq', 'terms', 'privacy'])->name('page');

Route::middleware('guest')->group(function () {
    Route::get('/login', [Site\AuthController::class, 'create'])->name('login');
    Route::post('/login', [Site\AuthController::class, 'send'])->middleware('throttle:6,1')->name('login.send');
    Route::get('/login/verify', [Site\AuthController::class, 'verifyForm'])->name('login.verify');
    Route::post('/login/verify', [Site\AuthController::class, 'verify'])->middleware('throttle:10,1')->name('login.check');
});
Route::post('/logout', [Site\AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/bullion', [Site\BullionController::class, 'store'])->name('bullion.store');
    Route::get('/jewelry/{product}/reserve', [Site\ReservationController::class, 'create'])->name('reserve.create');
    Route::post('/jewelry/{product}/reserve', [Site\ReservationController::class, 'store'])->name('reserve.store');
    Route::post('/sell', [Site\SellController::class, 'store'])->name('sell.store');
    Route::get('/account', [Site\AccountController::class, 'index'])->name('account');
    Route::get('/orders/{order}', [Site\AccountController::class, 'show'])->name('orders.show');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'create'])->middleware('guest')->name('login');
    Route::post('/login', [Admin\AuthController::class, 'store'])->middleware(['guest', 'throttle:6,1'])->name('login.store');

    Route::middleware('admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/prices', [Admin\PriceController::class, 'edit'])->name('prices');
        Route::put('/prices', [Admin\PriceController::class, 'update'])->name('prices.update');
        Route::post('/prices/halt', [Admin\PriceController::class, 'toggleHalt'])->name('prices.halt');
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
        Route::resource('branches', Admin\BranchController::class)->except(['show', 'destroy']);

        Route::get('/pos', [Admin\PosController::class, 'create'])->name('pos');
        Route::get('/pos/piece', [Admin\PosController::class, 'piece'])->name('pos.piece');
        Route::post('/pos', [Admin\PosController::class, 'store'])->name('pos.store');
        Route::get('/invoices', [Admin\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [Admin\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/print', [Admin\InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('/invoices/{invoice}/void', [Admin\InvoiceController::class, 'void'])->name('invoices.void');
        Route::get('/pieces/labels', [Admin\PieceController::class, 'labels'])->name('pieces.labels');
        Route::resource('pieces', Admin\PieceController::class)->except('show');
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('/shop', [Admin\ShopController::class, 'edit'])->name('shop');
        Route::put('/shop', [Admin\ShopController::class, 'update'])->name('shop.update');
    });
});
