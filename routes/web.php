<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\SlotDemoController;

Route::get('/', function () {
    return view('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/booking/create', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{id}', [BookingController::class, 'show'])->name('booking.show');
    Route::get('/booking/{id}/detail', [BookingController::class, 'detailPage'])->name('booking.detail');
    Route::get('/booking/{id}/edit', [BookingController::class, 'editPage'])->name('booking.edit');
    Route::post('/booking/{id}/update', [BookingController::class, 'update'])->name('booking.update');
    Route::post('/booking/{id}/status', [BookingController::class, 'updateStatus'])->name('booking.status');
    Route::post('/booking/{id}/prices', [BookingController::class, 'updatePrices'])->name('booking.prices');
    Route::post('/booking/{id}/payment', [BookingController::class, 'updatePaymentStatus'])->name('booking.payment');
    Route::post('/booking/{id}/cashback', [BookingController::class, 'addCashback'])->name('booking.cashback');
    Route::post('/booking/{id}/move-room', [BookingController::class, 'moveRoom'])->name('booking.move_room');
    Route::post('/booking/{id}/upgrade-room', [BookingController::class, 'upgradeRoom'])->name('booking.upgrade_room');
    Route::post('/booking/extra-bed-calc', [BookingController::class, 'calculateExtraBed'])->name('booking.extra_bed_calc');
    Route::get('/booking/{id}/nota', [BookingController::class, 'printNota'])->name('booking.nota');
    Route::get('/booking/{id}/printout', [BookingController::class, 'printout'])->name('booking.printout');
    Route::get('/booking/{id}/nota-cafe', [BookingController::class, 'notaCafe'])->name('booking.nota.cafe');
    Route::delete('/booking/{id}', [BookingController::class, 'destroy'])->name('booking.destroy');
    // Cafe module
    Route::get('/cafe', [CafeController::class,'index'])->name('cafe.index');
    Route::post('/cafe/products', [CafeController::class,'storeProduct'])->name('cafe.product.store');
    Route::post('/cafe/products/{id}/adjust', [CafeController::class,'adjustStock'])->name('cafe.product.adjust');
    Route::post('/cafe/orders', [CafeController::class,'storeOrder'])->name('cafe.order.store');
    Route::delete('/cafe/orders/{id}', [CafeController::class,'destroyOrder'])->name('cafe.order.destroy');
    // Rekap pemasukan bulanan (owner only)
    Route::middleware('role:owner')->group(function () {
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/print', [RekapController::class, 'print'])->name('rekap.print');
        Route::delete('/rekap/{ledgerId}', [RekapController::class, 'destroy'])->name('rekap.destroy');
        // Users management (owner only)
        Route::resource('users', UsersController::class)->except(['show']);
    });
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

// Demo route for booking slots (open route for quick preview)
Route::get('/slots-demo', [SlotDemoController::class, 'index'])->name('slots.demo');