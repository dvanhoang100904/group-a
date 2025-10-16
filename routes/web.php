<?php

use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/folders/create', [FolderController::class, 'create'])->name('folders.create');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
