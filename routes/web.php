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
use App\Http\Controllers\Api\DocumentDetailController;
use App\Http\Controllers\DocumentSharedController;
use App\Http\Controllers\MonHocController;
// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Nhóm route quản lý báo cáo
Route::prefix('dashboard')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::put('/reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});

// Folder
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

// Uploads - Ngoc Dan
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents List - Ngoc Dan
Route::get('/list-documents', [DocumentController::class, 'index'])->name('documents.index');

// Document Versions
Route::get('/documents/{documentId}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions.index');

// Document Accesses
Route::get('/documents/{documentId}/accesses', [DocumentAccessController::class, 'index'])->name('documents.accesses.index');
Route::put('/documents/{documentId}/accesses/settings', [DocumentAccessController::class, 'updateSettings'])->name('documents.accesses.updateSettings');

// Document Shared
Route::get('/shared', [DocumentSharedController::class, 'index'])->name('shared.index');


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
//Chi tiết tài liệu
Route::get('/documents/{id}', function ($id) {
    return view('documents.See_Document_Details.Document_Detail', ['documentId' => $id]);
})->name('documents.show');

// Môn học


Route::prefix('monhoc')->group(function () {
    Route::get('/', [MonHocController::class, 'index'])->name('monhoc.index');
    Route::get('/create', [MonHocController::class, 'create'])->name('monhoc.create');
    Route::post('/', [MonHocController::class, 'store'])->name('monhoc.store');
    Route::get('/{id}', [MonHocController::class, 'show'])->name('monhoc.show');
    Route::get('/{id}/edit', [MonHocController::class, 'edit'])->name('monhoc.edit');
    Route::put('/{id}', [MonHocController::class, 'update'])->name('monhoc.update');
    Route::delete('/{id}', [MonHocController::class, 'destroy'])->name('monhoc.destroy');
});
