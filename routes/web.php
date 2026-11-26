<?php

use App\Http\Controllers\AiAssistant\AnalyticsController;
use App\Http\Controllers\AiAssistant\ChatController;
use App\Http\Controllers\AiAssistant\MonitoringController;
use App\Http\Controllers\AiAssistant\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelompokExportController;
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

    Route::prefix('ai-assistant')->name('ai.')->group(function () {
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat/conversations', [ChatController::class, 'storeConversation'])->name('chat.conversations.store');
        Route::delete('/chat/conversations/{conversation}', [ChatController::class, 'destroyConversation'])
            ->whereNumber('conversation')
            ->name('chat.conversations.destroy');
        Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])
            ->whereNumber('conversation')
            ->name('chat.show');
        Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])
            ->whereNumber('conversation')
            ->name('chat.messages.store');

        Route::get('/analisis', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('/analisis/insight', [AnalyticsController::class, 'insight'])->name('analytics.insight');

        Route::get('/monev', [MonitoringController::class, 'index'])->name('monev.index');
        Route::get('/monev/{kelompok}', [MonitoringController::class, 'show'])
            ->whereNumber('kelompok')
            ->name('monev.show');
        Route::post('/monev/{kelompok}/analisis', [MonitoringController::class, 'analyze'])
            ->whereNumber('kelompok')
            ->name('monev.analyze');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/laporan/pratinjau', [ReportController::class, 'preview'])->name('reports.preview');
        Route::get('/laporan/{reportId}/word', [ReportController::class, 'word'])
            ->whereUuid('reportId')
            ->name('reports.word');
    });

    Route::get('/kelompok/tambah', [DashboardController::class, 'createKelompok'])->name('kelompok.create');
    Route::get('/kelompok/ekspor/{format}', [KelompokExportController::class, 'export'])
        ->whereIn('format', ['csv', 'xlsx'])
        ->name('kelompok.export');
    Route::get('/kelompok/{id}/edit', [DashboardController::class, 'editKelompok'])->name('kelompok.edit');
    Route::post('/kelompok', [DashboardController::class, 'storeKelompok'])->name('kelompok.store');
    Route::put('/kelompok/{id}', [DashboardController::class, 'updateKelompok'])->name('kelompok.update');
    Route::delete('/kelompok/{id}', [DashboardController::class, 'destroyKelompok'])->name('kelompok.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/kelompok/{kelompok}', [DashboardController::class, 'showKelompok'])->name('kelompok.show');

require __DIR__.'/auth.php';
