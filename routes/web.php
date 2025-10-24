<?php

use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UploadController;
// use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\UserController;



Route::get('/dashboard', [DashboardController::class, 'index']);



Route::get('/folders/search', [FolderController::class, 'search'])->name('folders.search');

Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])->name('folders.edit');
Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');






// Uplods
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents
Route::get('/my-documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show'); // ✅ thêm dòng này
Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

// Nhóm route quản lý báo cáo
Route::prefix('dashboard')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::put('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});

Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])->name('folders.edit');
Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

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

// Profile
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.view');
Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::get('/documents/{document}/versions', [DocumentVersionController::class, 'index']);

Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])

    ->name('documents.versions.index');

Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])
    ->name('documents.versions.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');


//profile
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.view');
Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
