<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController; // Tambahkan baris ini

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Route Kelompok Binaan
    Route::get('/kelompok/tambah', [DashboardController::class, 'createKelompok'])->name('kelompok.create');
    Route::post('/kelompok', [DashboardController::class, 'storeKelompok'])->name('kelompok.store');
    Route::delete('/kelompok/{id}', [DashboardController::class, 'destroyKelompok'])->name('kelompok.destroy');

    // Route Profil Pengguna (Bawaan Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';