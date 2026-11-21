<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/visualisasi', [DashboardController::class, 'index'])->name('visualisasi');
Route::get('/kelompok', [DashboardController::class, 'groups'])->name('kelompok.index');
Route::get('/kawasan-nte', [DashboardController::class, 'areas'])->name('kawasan.index');
Route::get('/peta-kelompok', [DashboardController::class, 'map'])->name('kelompok.map');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/kelompok/tambah', [DashboardController::class, 'createKelompok'])->name('kelompok.create');
    Route::get('/kelompok/{id}/edit', [DashboardController::class, 'editKelompok'])->name('kelompok.edit');
    Route::post('/kelompok', [DashboardController::class, 'storeKelompok'])->name('kelompok.store');
    Route::put('/kelompok/{id}', [DashboardController::class, 'updateKelompok'])->name('kelompok.update');
    Route::delete('/kelompok/{id}', [DashboardController::class, 'destroyKelompok'])->name('kelompok.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
