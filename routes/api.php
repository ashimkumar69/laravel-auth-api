<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {

    $api->group(["prefix" => "auth"], function ($api) {

        $api->group(['middleware' => 'api.throttle', 'limit' => 100, 'expires' => 1], function ($api) {

            $api->post("/login", [AuthController::class, "login"]);
            // $api->post("/forgot-password", 'forgotPassword');
            // $api->post("/reset-password", 'resetPassword');
        });
        $api->group(['middleware' => 'auth:sanctum'], function ($api) {
            $api->post("/logout", [AuthController::class, "logout"]);
        });
    });
    $api->group(['middleware' => 'auth:sanctum', "prefix" => "admin"], function ($api) {
        $api->get("/user", [UserController::class, 'index']);
        // $api->post("/send-verify-email", 'sendVerificationMail');
        // $api->post("/verify-email", 'verifyMail');
    });
});

// Route::prefix("auth")->controller(AuthController::class)
//     ->group(function () {
//         Route::post("/login", 'login')->middleware(['throttle:3,1']);
//         Route::post("/logout", 'logout')->middleware(['auth:sanctum']);
//         Route::post('/forgot-password', 'forgotPassword')->middleware(['throttle:3,1']);
//         Route::post('/reset-password', 'resetPassword');
//     });

// Route::prefix("admin")->middleware('auth:sanctum')
//     ->controller(UserController::class)->group(function () {
//         Route::get("/user", 'index');
//         Route::post("/send-verify-email", 'sendVerificationMail');
//         Route::post("/verify-email", 'verifyMail');
//     });
