<?php

use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UploadController;


use App\Http\Controllers\DocumentVersionController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

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

Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])
    ->name('documents.versions.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');

