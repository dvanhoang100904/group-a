<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentAccessController;
use App\Http\Controllers\Api\DocumentDetailController;
use App\Http\Controllers\Api\TypeControllers;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\DocumentSharedController;
use App\Http\Controllers\Api\DocumentUpdateController;
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
    //Route::delete('/documents/{document}', [UploadController::class, 'destroy']);
    Route::delete('/upload/documents/{document}', [UploadController::class, 'destroy'])->name('upload.destroy');

    Route::post('/documents/upload', [UploadController::class, 'store'])->name('api.upload.store');
});

// danh sách tài liệu của người dùng hiện tại 
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents', [DocumentController::class, 'getDocuments']);

// chi tiết tài liệu
Route::get('/documents/{id}', [DocumentDetailController::class, 'show']);
Route::get('/documents/{id}/detail', [DocumentDetailController::class, 'show']);

// danh sách loại tài liệu và môn học
Route::get('/types', [TypeControllers::class, 'index']);
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/folders', [FolderController::class, 'getFolder']);
Route::get('documents/{id}/detail', [DocumentDetailController::class, 'show']);
Route::get('/folders/tree', [FolderController::class, 'indexViewTree']);

// =========================
// 📁 Folder API Routes
// =========================
// Thêm vào cuối nhóm Folder API Routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/folders', [FolderController::class, 'index']);
    Route::get('/folders/{folder}', [FolderController::class, 'show']);
    Route::post('/folders', [FolderController::class, 'store']);
    Route::put('/folders/{folder}', [FolderController::class, 'update']);
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);
    Route::get('/folders/search', [FolderController::class, 'search']);

    // Route xóa document
    Route::delete('/documents/{id}', [DocumentAccessController::class, 'deleteDocument']);

    // Folder Share Routes
    Route::post('/folders/{folderId}/share', [FolderController::class, 'shareFolder']);
    Route::post('/folders/{folderId}/unshare', [FolderController::class, 'unshareFolder']);
    Route::get('/folders/{folderId}/shared-users', [FolderController::class, 'getSharedUsers']);
    Route::get('/users/search', [FolderController::class, 'searchUsers']);
});
Route::get('/api/documents', [DocumentController::class, 'getDocuments']);
Route::get('/api/documents/{id}/detail', [DocumentController::class, 'getDocumentDetail']);
Route::get('/api/types', [TypeControllers::class, 'index']);
Route::get('/api/subjects', [SubjectController::class, 'index']);

Route::put('/documents/{id}', [DocumentUpdateController::class, 'update']);
