<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentVersionController;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/documents/{document}/versions', [DocumentVersionController::class, 'index'])
    ->name('documents.versions.index');
