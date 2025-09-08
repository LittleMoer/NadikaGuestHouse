<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\KamarController;

Route::get('/', function () {
    return view('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {return view('dashboard');});
    Route::get('/booking', function () {return view('booking');});
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