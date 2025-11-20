<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentAccessController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\KhoaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentSharedController;
use App\Http\Controllers\MonHocController;
use App\Http\Controllers\AccessLogController;

// Login
Route::get('/', [UserController::class, 'login'])->name('login')->middleware('redirectIf.auth');
Route::post('/', [UserController::class, 'authLogin'])->name('auth.login')->middleware('redirectIf.auth');

// Logout
Route::get('logout', function () {
	return redirect()->route('login');
});
Route::post('logout', [UserController::class, 'logout'])->name('logout')->middleware('require.login');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('require.login', 'check.role:Admin');

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

// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions.index')->middleware('require.login', 'check.role:Admin,Giảng viên,Sinh viên');;

// Document Accesses
Route::get('/documents/{documentId}/accesses', [DocumentAccessController::class, 'index'])->name('documents.accesses.index')->middleware('require.login', 'check.role:Admin,Giảng viên,Sinh viên');;
Route::put('/documents/{documentId}/accesses/settings', [DocumentAccessController::class, 'updateSettings'])->name('documents.accesses.updateSettings')->middleware('require.login', 'check.role:Admin,Giảng viên,Sinh viên');;

// Document Shared
Route::get('/shared', [DocumentSharedController::class, 'index'])->name('shared.index')->middleware('require.login', 'check.role:Admin,Giảng viên,Sinh viên');;

// Profile
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.view');
Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');

// Khoa/bộ môn
Route::prefix('khoa')->group(function () {
	Route::get('/', [KhoaController::class, 'index'])->name('khoa.index');
	Route::get('/create', [KhoaController::class, 'create'])->name('khoa.create');
	Route::post('/', [KhoaController::class, 'store'])->name('khoa.store');
	Route::get('/{id}', [KhoaController::class, 'show'])->name('khoa.show');
	Route::get('/{id}/edit', [KhoaController::class, 'edit'])->name('khoa.edit');
	Route::put('/{id}', [KhoaController::class, 'update'])->name('khoa.update');
	Route::delete('/{id}', [KhoaController::class, 'destroy'])->name('khoa.destroy');
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
});

// Type documents
// Route::prefix('types')->name('types.')->group(function () {
// 	Route::get('/', [TypeController::class, 'index'])->name('index');
// 	Route::get('/create', [TypeController::class, 'create'])->name('create');
// 	Route::post('/', [TypeController::class, 'store'])->name('store');
// 	Route::get('/{type}', [TypeController::class, 'show'])->name('show');
// 	Route::get('/{type}/edit', [TypeController::class, 'edit'])->name('edit');
// 	Route::put('/{type}', [TypeController::class, 'update'])->name('update');
// 	Route::delete('/{type}', [TypeController::class, 'destroy'])->name('destroy');
// });

Route::prefix('types')->name('types.')->group(function () {
	Route::get('/', [TypeController::class, 'index'])->name('index');
	Route::get('/create', [TypeController::class, 'create'])->name('create');
	Route::post('/', [TypeController::class, 'store'])->name('store');
	Route::get('/{type}', [TypeController::class, 'show'])->name('show');
	Route::get('/{type}/edit', [TypeController::class, 'edit'])->name('edit');
	Route::put('/{type}', [TypeController::class, 'update'])->name('update');
	Route::delete('/{type}', [TypeController::class, 'destroy'])->name('destroy');

	// Xuất Excel
	Route::get('/export-excel', [TypeController::class, 'exportExcel'])->name('exportExcel');
});

// Tags
Route::prefix('tags')->name('tags.')->group(function () {
	Route::get('/', [App\Http\Controllers\TagController::class, 'index'])->name('index');
	Route::get('/create', [App\Http\Controllers\TagController::class, 'create'])->name('create');
	Route::post('/', [App\Http\Controllers\TagController::class, 'store'])->name('store');
	Route::get('/{tag}', [App\Http\Controllers\TagController::class, 'show'])->name('show');
	Route::get('/{tag}/edit', [App\Http\Controllers\TagController::class, 'edit'])->name('edit');
	Route::put('/{tag}', [App\Http\Controllers\TagController::class, 'update'])->name('update');
	Route::delete('/{tag}', [App\Http\Controllers\TagController::class, 'destroy'])->name('destroy');
});


Route::prefix('access-logs')->name('access.logs.')->middleware(['web', 'require.login', 'check.role:Admin,Giảng viên'])->group(function () {
	Route::get('/', [AccessLogController::class, 'index'])->name('index');
	Route::get('/{id}', [AccessLogController::class, 'show'])->name('show');

	// ✅ Thêm route thống kê
	Route::get('/statistics', [AccessLogController::class, 'statistics'])->name('statistics');

	// Route cho nhật ký cá nhân nếu cần
	Route::get('/my', [AccessLogController::class, 'myLogs'])->name('my');
});
