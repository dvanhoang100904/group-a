<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentVersionActionController;
use App\Http\Controllers\Api\DocumentVersionCompareController;
use App\Http\Controllers\Api\DocumentAccessController;
use App\Http\Controllers\Api\DocumentAccessActionController;
use App\Http\Controllers\Api\DocumentDetailController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index']);
Route::get('/documents/{documentId}/versions/compare', [DocumentVersionCompareController::class, 'compare']);
Route::get('/documents/{documentId}/versions/uploaders', [DocumentVersionController::class, 'uploaders']);
Route::get('/documents/{documentId}/versions/{versionId}', [DocumentVersionController::class, 'show']);
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionController::class, 'preview']);
Route::post('/documents/{id}/versions', [DocumentVersionActionController::class, 'store']);
Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentVersionActionController::class, 'download']);
Route::post('/documents/{documentId}/versions/{versionId}/restore', [DocumentVersionActionController::class, 'restore']);
Route::delete('/documents/{documentId}/versions/{versionId}', [DocumentVersionActionController::class, 'destroy']);

// Document Accesses
Route::get('/documents/{id}/accesses', [DocumentAccessController::class, 'index']);
Route::get('/documents/{id}/accesses/users', [DocumentAccessController::class, 'users']);
Route::get('/documents/{id}/accesses/roles', [DocumentAccessController::class, 'roles']);
Route::post('/documents/{id}/accesses', [DocumentAccessActionController::class, 'store']);

// =========================
// 👤 Users
// =========================
//Route::get('/users', [UserApiController::class, 'index']);

// =========================
// 📤 Document Uploads (auth required)
// =========================
// Chỉ dùng khi cần auth middleware
Route::middleware(['api'])->group(function () {
    Route::get('/upload/metadata', [UploadController::class, 'getMetadata']);
    Route::get('/download/{version}', [UploadController::class, 'download']);
    Route::delete('/documents/{document}', [UploadController::class, 'destroy']);
});

// danh sách tài liệu của người dùng hiện tại 
Route::get('/my-documents', [DocumentController::class, 'index']);
Route::get('/documents', [DocumentController::class, 'getDocuments']);

// chi tiết tài liệu
Route::get('/documents/{id}', [DocumentDetailController::class, 'show']);
Route::get('/documents/{id}/detail', [DocumentDetailController::class, 'show']);
