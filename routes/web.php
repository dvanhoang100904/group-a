<?php

use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index']);
