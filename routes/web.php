<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UploadController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Upload
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents
Route::get('/my-documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show'); // ✅ thêm dòng này
Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');