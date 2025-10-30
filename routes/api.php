<?php

use App\Http\Controllers\Api\DocumentAccessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentVersionCompareController;
use App\Http\Controllers\Api\DocumentVersionActionController;
use App\Http\Controllers\Api\UserController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Document Versions
Route::get('/documents/{id}/versions', [DocumentVersionController::class, 'index']);
// Compare
Route::get('/documents/{documentId}/versions/compare', [DocumentVersionCompareController::class, 'compare']);
// Detail
Route::get('/documents/{documentId}/versions/{versionId}', [DocumentVersionController::class, 'show']);
// Preview
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionController::class, 'preview']);
// Upload
Route::post('/documents/{id}/versions', [DocumentVersionActionController::class, 'store']);
// Download
Route::get('/documents/{documentId}/versions/{versionId}/download', [DocumentVersionActionController::class, 'download']);
// Restore
Route::post('/documents/{documentId}/versions/{versionId}/restore', [DocumentVersionActionController::class, 'restore']);
// Delete
Route::delete('/documents/{documentId}/versions/{versionId}', [DocumentVersionActionController::class, 'destroy']);
// Document Accesses
Route::get('/documents/{id}/accesses', [DocumentAccessController::class, 'index']);


// Users
Route::get('/users', [UserController::class, 'index']);
