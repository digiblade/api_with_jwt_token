<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRMController;
use App\Http\Controllers\OpenCRMController;
use App\Http\Controllers\ProductController;

Route::get('/unauthorized', function () {
    return response()->json(['error', 'Unauthorized'], 403,);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/crm-data-insert', [CRMController::class, 'createCRMRecord']);
Route::post('/crm-data-get', [CRMController::class, 'getCRMRecord']);
Route::post('/crm-multi-label-data-get', [CRMController::class, 'getCRMRecordWithMultiLabel']);


// open

Route::post('/open/crm-data-get', [OpenCRMController::class, 'getCRMRecord']);
Route::post('/open/crm-multi-label-data-get', [OpenCRMController::class, 'getCRMRecordWithMultiLabel']);


Route::post('/get-page-details', [ProductController::class, 'getProductDetails']);
