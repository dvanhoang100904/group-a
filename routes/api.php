<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentVersionApiController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/documents/{id}/versions', [DocumentVersionApiController::class, 'index']);
Route::get('/versions/{id}/preview', [DocumentVersionApiController::class, 'preview']);
Route::post('/documents/{id}/versions/upload', [DocumentVersionApiController::class, 'upload']);
