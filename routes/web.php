<?php

use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])
    ->name('documents.versions.index');

// Upload
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents
Route::get('/my-documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show'); // ✅ thêm dòng này
Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');