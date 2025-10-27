<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentVersionApiController;
use App\Http\Controllers\Api\UserApiController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionApiController::class, 'index']);
Route::get('/documents/{documentId}/versions/{versionId}', [DocumentVersionApiController::class, 'show']);
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionApiController::class, 'preview']);
Route::post('/documents/{id}/versions', [DocumentVersionApiController::class, 'store']);
Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentVersionApiController::class, 'download']);
Route::post('/documents/{documentId}/versions/{versionId}/restore', [DocumentVersionApiController::class, 'restore']);
Route::delete('/documents/{documentId}/versions/{versionId}', [DocumentVersionApiController::class, 'destroy']);



// Users
Route::get('/users', [UserApiController::class, 'index']);
