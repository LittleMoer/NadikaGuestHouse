<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\CafeController;

Route::get('/', function () {
    return view('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{id}', [BookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{id}/update', [BookingController::class, 'update'])->name('booking.update');
    Route::post('/booking/{id}/status', [BookingController::class, 'updateStatus'])->name('booking.status');
    Route::post('/booking/{id}/prices', [BookingController::class, 'updatePrices'])->name('booking.prices');
    Route::post('/booking/{id}/payment', [BookingController::class, 'updatePaymentStatus'])->name('booking.payment');
    Route::get('/booking/{id}/nota', [BookingController::class, 'printNota'])->name('booking.nota');
    Route::delete('/booking/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
    // Cafe module
    Route::get('/cafe', [CafeController::class,'index'])->name('cafe.index');
    Route::post('/cafe/products', [CafeController::class,'storeProduct'])->name('cafe.product.store');
    Route::post('/cafe/products/{id}/adjust', [CafeController::class,'adjustStock'])->name('cafe.product.adjust');
    Route::post('/cafe/orders', [CafeController::class,'storeOrder'])->name('cafe.order.store');
    // Ganti route list orders ke /cafeorders agar sesuai dengan view yang tersedia
    Route::get('/cafeorders', [CafeController::class,'ordersList'])->name('cafe.orders');
    Route::get('/penginap', function () {return view('penginap');});
    Route::get('/penginap', [BookingController::class, 'penginap'])->name('penginap');
    Route::post('/penginap',[BookingController::class, 'penginapcreate'])->name('penginap.create');
    Route::post('/penginap/{id}',[BookingController::class, 'penginapedit'])->name('penginap.edit');
    Route::delete('/penginap/{id}',[BookingController::class, 'penginapdestroy'])->name('penginap.destroy');

    // Resource route for kamar CRUD
    Route::resource('kamar', KamarController::class);
});
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');