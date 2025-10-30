<?php



use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentVersionActionController;
use App\Http\Controllers\Api\DocumentVersionCompareController;
use App\Http\Controllers\Api\DocumentAccessController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index']);
Route::get('/documents/{documentId}/versions/compare', [DocumentVersionCompareController::class, 'compare']);
Route::get('/documents/{documentId}/versions/{versionId}', [DocumentVersionController::class, 'show']);
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionController::class, 'preview']);
Route::post('/documents/{id}/versions', [DocumentVersionActionController::class, 'store']);
Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentVersionActionController::class, 'download']);
Route::post('/documents/{documentId}/versions/{versionId}/restore', [DocumentVersionActionController::class, 'restore']);
Route::delete('/documents/{documentId}/versions/{versionId}', [DocumentVersionActionController::class, 'destroy']);
// Document Accesses
Route::get('/documents/{id}/accesses', [DocumentAccessController::class, 'index']);

// Users
//Route::get('/users', [UserApiController::class, 'index']);
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


Route::get('/my-documents', [DocumentController::class, 'index']);
Route::get('/documents', [DocumentController::class, 'getDocuments']);