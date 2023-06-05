<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRMController;

Route::get('/unauthorized', function () {
    return response()->json(['error', 'Unauthorized'], 403,);;
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/crm-data-insert', [CRMController::class, 'createCRMRecord']);
Route::post('/crm-data-get', [CRMController::class, 'getCRMRecord']);
