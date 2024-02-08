<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')
    ->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
    });

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::apiResource('users', UserController::class)
            ->middleware('admin');
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);

        Route::get('/orders/{id}/order-items', [OrderController::class, 'orderItems'])
            ->middleware('admin');
        Route::apiResource('orders', OrderController::class)
            ->middleware('admin');

        Route::apiResource('order-items', OrderItemController::class)
            ->middleware('admin');
    });
