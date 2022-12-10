<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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

Route::prefix("auth")->controller(AuthController::class)->group(function () {
    Route::post("/login", 'login')->middleware(['throttle:6,1']);
    Route::post("/logout", 'logout')->middleware(['auth:sanctum']);
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
});

Route::prefix("admin")->middleware('auth:sanctum')->controller(UserController::class)->group(function () {
    Route::get("/user", 'index');
});
