<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:api')->group(function () {
    Route::get('/directories', [DirectoryController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/directories', [DirectoryController::class, 'create']);
    Route::delete('/directories/{directory}', [DirectoryController::class, 'delete'])->middleware('auth:sanctum');
    Route::put('/directories/{directory}', [DirectoryController::class, 'rename']);

    Route::post('/files/upload', [FileController::class, 'upload'])->middleware('auth:sanctum');
    Route::delete('/files/{file}', [FileController::class, 'delete']);
    Route::put('/files/{file}', [FileController::class, 'rename']);
    Route::get('/files/{file}', [FileController::class, 'info']);
    Route::post('/files/{file}/toggle-public', [FileController::class, 'togglePublic']);
    Route::get('/disk-usage', [FileController::class, 'diskUsage']);
    Route::get('/files', [FileController::class, 'index'])->middleware('auth:sanctum');
    Route::middleware('auth:sanctum')->get('/directories/{id}/files', [DirectoryController::class, 'getFiles']);
});

Route::get('/download/{uniqueLink}', [FileController::class, 'download']);

Route::middleware('auth:sanctum')->get('/directories/{id}/files', [DirectoryController::class, 'getFiles']);

Route::post('webhook', [\App\Http\Controllers\Api\TelegramController::class, 'handleWebhook']);
