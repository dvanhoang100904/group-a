<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KhoaController;
use App\Http\Controllers\DocumentAccessController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Nhóm route quản lý báo cáo
Route::prefix('dashboard')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::put('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});

// Folder - yen đẹp trai
Route::prefix('folders')->name('folders.')->group(function () {
    Route::get('/test-create', function () {
        return view('folders.test-create');
    })->name('test-create');
    Route::get('/', [FolderController::class, 'index'])->name('index');
    Route::get('/search', [FolderController::class, 'search'])->name('search');
    Route::get('/create', [FolderController::class, 'create'])->name('create');
    Route::post('/', [FolderController::class, 'store'])->name('store');
    Route::get('/{folder}', [FolderController::class, 'show'])->name('show');
    Route::get('/{folder}/edit', [FolderController::class, 'edit'])->name('edit');
    Route::put('/{folder}', [FolderController::class, 'update'])->name('update');
    Route::delete('/{folder}', [FolderController::class, 'destroy'])->name('destroy');
});

// Uploads
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents
Route::get('/my-documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');


// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions.index');
// Document Accesses
Route::get('/documents/{id}/accesses', [DocumentAccessController::class, 'index'])->name('documents.accesses.index');

// Profile
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.view');
Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');


//Khoa/bộ môn
Route::prefix('khoa')->group(function () {
    Route::get('/', [KhoaController::class, 'index'])->name('khoa.index');
    Route::get('/create', [KhoaController::class, 'create'])->name('khoa.create');
    Route::post('/', [KhoaController::class, 'store'])->name('khoa.store');
    Route::get('/{id}', [KhoaController::class, 'show'])->name('khoa.show');
    Route::get('/{id}/edit', [KhoaController::class, 'edit'])->name('khoa.edit');
    Route::put('/{id}', [KhoaController::class, 'update'])->name('khoa.update');
    Route::delete('/{id}', [KhoaController::class, 'destroy'])->name('khoa.destroy');
});
