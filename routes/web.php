<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route; // Tambahkan baris ini

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/peta-kelompok', [DashboardController::class, 'map'])->name('kelompok.map');

    // Route Kelompok Binaan
    Route::get('/kelompok/tambah', [DashboardController::class, 'createKelompok'])->name('kelompok.create');
    Route::get('/kelompok/{id}/edit', [DashboardController::class, 'editKelompok'])->name('kelompok.edit');
    Route::post('/kelompok', [DashboardController::class, 'storeKelompok'])->name('kelompok.store');
    Route::put('/kelompok/{id}', [DashboardController::class, 'updateKelompok'])->name('kelompok.update');
    Route::delete('/kelompok/{id}', [DashboardController::class, 'destroyKelompok'])->name('kelompok.destroy');

    // Route Profil Pengguna (Bawaan Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
