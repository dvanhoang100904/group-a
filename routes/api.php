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
Route::get('/documents/{documentId}/versions/{versionId}/preview', [DocumentVersionApiController::class, 'preview']);
Route::post('/documents/{id}/versions', [DocumentVersionApiController::class, 'store']);

// Users
Route::get('/users', [UserApiController::class, 'index']);
