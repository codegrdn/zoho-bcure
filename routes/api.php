<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::name('auth')->post('/auth', [AuthController::class, 'auth']);
Route::name('auth')->post('/refresh-access-token', [AuthController::class, 'refreshAccessToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::name('orders')->post('/orders', [ApiController::class, 'createSalesOrderDoc']);
    Route::name('cases')->post('/cases', [ApiController::class, 'createCaseDoc']);
});

Route::name('info')->get('/info', function (Request $request) {
    return response(json_encode(['Message' => 'Unauthenticated.']), 401);
});
