<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentAccessController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\KhoaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TypesController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentSharedController;
use App\Http\Controllers\MonHocController;
use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\TagController;

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

// Reports
Route::prefix('reports')->group(function () {
	Route::get('/', [ReportController::class, 'index'])->name('reports.index');
	Route::get('/{id}', [ReportController::class, 'show'])->name('reports.show');
	Route::put('/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
});

// Uploads
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Documents
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

// Trang chi tiết tài liệu (Blade)
Route::get('/documents/{id}', function ($id) {
	return view('documents.See_Document_Details.Document_Detail', compact('id'));
})->name('documents.show');

// Route tải tài liệu tại chi tiết
Route::get(
	'/documents/versions/{versionId}/download',
	[DocumentController::class, 'downloadVersion']
)->name('documents.version.download');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Access logs
Route::prefix('access-logs')->name('access.logs.')->group(function () {
	Route::get('/', [AccessLogController::class, 'index'])->name('index');
	Route::get('/{id}', [AccessLogController::class, 'show'])->name('show');
	Route::get('/statistics', [AccessLogController::class, 'statistics'])->name('statistics');
	Route::get('/my', [AccessLogController::class, 'myLogs'])->name('my');
});

// Document Shared
Route::get('/shared', [DocumentSharedController::class, 'index'])->name('shared.index');

// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions.index');

// Document Accesses
Route::get('/documents/{documentId}/accesses', [DocumentAccessController::class, 'index'])->name('documents.accesses.index');
Route::put('/documents/{documentId}/accesses/settings', [DocumentAccessController::class, 'updateSettings'])->name('documents.accesses.updateSettings');

// Khoa/bộ môn
Route::prefix('khoa')->group(function () {
	Route::get('/', [KhoaController::class, 'index'])->name('khoa.index');
	Route::get('/create', [KhoaController::class, 'create'])->name('khoa.create');
	Route::post('/', [KhoaController::class, 'store'])->name('khoa.store');
	Route::get('/{id}', [KhoaController::class, 'show'])->name('khoa.show');
	Route::get('/{id}/edit', [KhoaController::class, 'edit'])->name('khoa.edit');
	Route::put('/{id}', [KhoaController::class, 'update'])->name('khoa.update');
	Route::delete('/{id}', [KhoaController::class, 'destroy'])->name('khoa.destroy');
	Route::get('/export/excel', [KhoaController::class, 'exportExcel'])->name('khoa.export.excel');
	Route::get('/export/pdf', [KhoaController::class, 'exportPDF'])->name('khoa.export.pdf');
});

// Môn học
Route::prefix('monhoc')->group(function () {
	Route::get('/', [MonHocController::class, 'index'])->name('monhoc.index');
	Route::get('/create', [MonHocController::class, 'create'])->name('monhoc.create');
	Route::post('/', [MonHocController::class, 'store'])->name('monhoc.store');
	Route::get('/{id}', [MonHocController::class, 'show'])->name('monhoc.show');
	Route::get('/{id}/edit', [MonHocController::class, 'edit'])->name('monhoc.edit');
	Route::put('/{id}', [MonHocController::class, 'update'])->name('monhoc.update');
	Route::delete('/{id}', [MonHocController::class, 'destroy'])->name('monhoc.destroy');
	Route::get('monhoc/{id}/documents', [MonHocController::class, 'documents'])
		->name('monhoc.documents');
	Route::get('/export/excel', [MonHocController::class, 'exportExcel'])->name('monhoc.export.excel');
	Route::get('/export/pdf', [MonHocController::class, 'exportPDF'])->name('monhoc.export.pdf');
});

// Types
Route::prefix('types')->name('types.')->group(function () {
	Route::get('/export-excel', [TypesController::class, 'exportExcel'])->name('exportExcel');
	Route::get('/', [TypesController::class, 'index'])->name('index');
	Route::get('/create', [TypesController::class, 'create'])->name('create');
	Route::post('/', [TypesController::class, 'store'])->name('store');
	Route::get('/{type}', [TypesController::class, 'show'])->name('show');
	Route::get('/{type}/edit', [TypesController::class, 'edit'])->name('edit');
	Route::put('/{type}', [TypesController::class, 'update'])->name('update');
	Route::delete('/{type}', [TypesController::class, 'destroy'])->name('destroy');
});

// Tags
Route::prefix('tags')->name('tags.')->group(function () {
	Route::get('/', [TagController::class, 'index'])->name('index');
	Route::get('/create', [TagController::class, 'create'])->name('create');
	Route::post('/', [TagController::class, 'store'])->name('store');
	Route::get('/{tag}', [TagController::class, 'show'])->name('show');
	Route::get('/{tag}/edit', [TagController::class, 'edit'])->name('edit');
	Route::put('/{tag}', [TagController::class, 'update'])->name('update');
	Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
});
