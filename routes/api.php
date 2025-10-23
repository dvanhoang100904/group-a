<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentVersionApiController as VersionApiController;
use App\Http\Controllers\Api\UserApiController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Document Versions
Route::get('/documents/{id}/versions', [VersionApiController::class, 'index']);
Route::get('/versions/{id}/preview', [VersionApiController::class, 'preview']);
Route::post('/documents/{id}/versions/upload', [VersionApiController::class, 'upload']);

// Users
Route::get('/users', [UserApiController::class, 'index']);
