<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentAccessController;
use App\Http\Controllers\Api\DocumentDetailController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\DocumentSharedController;





// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Document Versions
Route::get('/documents/{documentId}/versions', [DocumentVersionController::class, 'index']);
Route::get('/documents/{documentId}/versions/compare', [DocumentVersionController::class, 'compare']);
Route::get('/documents/{documentId}/versions/users', [DocumentVersionController::class, 'listUsers']);
Route::get('/documents/{documentId}/versions/{versionId}', [DocumentVersionController::class, 'show']);
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionController::class, 'preview']);
Route::post('/documents/{documentId}/versions', [DocumentVersionController::class, 'store']);
Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentVersionController::class, 'download']);
Route::post('/documents/{documentId}/versions/{versionId}/restore', [DocumentVersionController::class, 'restore']);
Route::delete('/documents/{documentId}/versions/{versionId}', [DocumentVersionController::class, 'destroy']);

// Document Accesses
Route::get('/documents/{documentId}/accesses', [DocumentAccessController::class, 'index']);
Route::get('/documents/{documentId}/accesses/users', [DocumentAccessController::class, 'listUsers']);
Route::get('/documents/{documentId}/accesses/roles', [DocumentAccessController::class, 'listRoles']);
Route::post('/documents/{documentId}/accesses', [DocumentAccessController::class, 'store']);
Route::put('/documents/{documentId}/accesses/{accessId}', [DocumentAccessController::class, 'update']);
Route::delete('/documents/{documentId}/accesses/{accessId}', [DocumentAccessController::class, 'destroy']);

// Document Shared
Route::get('/shared', [DocumentSharedController::class, 'index']);
Route::get('/shared/users', [DocumentSharedController::class, 'listUsers']);

// =========================
// 👤 Users
// =========================
// Route::get('/users', [UserApiController::class, 'index']);

// =========================
// 📤 Document Uploads (auth required)
// =========================
// Chỉ dùng khi cần auth middleware
Route::middleware(['api'])->group(function () {
    Route::get('/upload/metadata', [UploadController::class, 'getMetadata']);
    Route::get('/download/{version}', [UploadController::class, 'download']);
    Route::delete('/documents/{document}', [UploadController::class, 'destroy']);

    Route::post('/documents/upload', [UploadController::class, 'store'])->name('api.upload.store');
});

// danh sách tài liệu của người dùng hiện tại 
Route::get('/list-documents', [DocumentController::class, 'index']);
Route::get('/documents', [DocumentController::class, 'getDocuments']);

// chi tiết tài liệu
Route::get('/documents/{id}', [DocumentDetailController::class, 'show']);
Route::get('/documents/{id}/detail', [DocumentDetailController::class, 'show']);

// danh sách loại tài liệu và môn học
Route::get('/types', [TypeController::class, 'index']);
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/folders', [FolderController::class, 'index']);
