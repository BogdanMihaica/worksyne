<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\CompanyUserSeniorityController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionPlanFeatureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserWorkstreamController;
use App\Http\Controllers\WorkstreamController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::middleware('auth.token')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('company-users', CompanyUserController::class);
    Route::apiResource('company-user-seniorities', CompanyUserSeniorityController::class);
    Route::apiResource('features', FeatureController::class);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::apiResource('subscription-plan-features', SubscriptionPlanFeatureController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('user-workstreams', UserWorkstreamController::class);
    Route::apiResource('workstreams', WorkstreamController::class);
});
