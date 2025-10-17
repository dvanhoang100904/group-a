<?php

use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentVersionController;
use Illuminate\Support\Facades\Route;

Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::get('/folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])->name('folders.edit');
Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');



Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])
    ->name('documents.versions.index');
