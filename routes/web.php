<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;


Route::get('/', fn() => redirect('/dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('dashboard')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::put('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});

// Dashboard chính
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Nhóm route quản lý báo cáo
Route::prefix('dashboard')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::put('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index']);


