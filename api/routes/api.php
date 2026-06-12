<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CapacityModelController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyForecastController;
use App\Http\Controllers\CompanySubscriptionCheckoutController;
use App\Http\Controllers\CompanyOverviewController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\CompanyUserSeniorityController;
use App\Http\Controllers\CompanyNotificationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionPlanFeatureController;
use App\Http\Controllers\TimelogController;
use App\Http\Controllers\TimeoffRequestController;
use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserWorkstreamController;
use App\Http\Controllers\WorkstreamController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});
Route::get('pricing', [PricingController::class, 'index']);
Route::post('contact', ContactController::class)->middleware('throttle:5,1');

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('auth/forgot-password', [PasswordResetController::class, 'requestLink'])->middleware('throttle:5,1');
Route::post('auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
Route::middleware('auth.token')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [DashboardController::class, 'show']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::middleware('role:admin')->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index']);
    });

    Route::middleware('role:company_admin')->group(function () {
        Route::get('company-timelogs', [TimelogController::class, 'companyIndex'])->middleware('feature:time-logging');
        Route::post('company-subscription/checkout', [CompanySubscriptionCheckoutController::class, 'store']);
        Route::get('company-subscription/checkout/confirm', [CompanySubscriptionCheckoutController::class, 'confirm']);
        Route::post('company-subscription/downgrade', [CompanySubscriptionCheckoutController::class, 'downgrade']);
        Route::get('company-overview', [CompanyOverviewController::class, 'show']);
        Route::get('company-forecast', [CompanyForecastController::class, 'show'])->middleware('feature:forecast');
        Route::get('company-notifications/recipients', [CompanyNotificationController::class, 'recipients'])->middleware('feature:notifications');
        Route::post('company-notifications', [CompanyNotificationController::class, 'store'])->middleware('feature:notifications');
        Route::get('company-timesheet', [TimeoffRequestController::class, 'companyTimesheet'])->middleware('feature:company-timeoff');
        Route::get('company-timeoff-requests', [TimeoffRequestController::class, 'companyIndex'])->middleware('feature:company-timeoff');
        Route::patch('company-timeoff-requests/{timeoffRequest}/status', [TimeoffRequestController::class, 'companyUpdateStatus'])->middleware('feature:company-timeoff');
        Route::get('company-work-logs', [WorkLogController::class, 'companyIndex']);
        Route::get('company-work-logs/options', [WorkLogController::class, 'companyOptions']);
        Route::get('company-work-logs/summary', [WorkLogController::class, 'companySummary']);
        Route::get('workstreams/{workstream}/capacity-models', [CapacityModelController::class, 'forWorkstream'])->middleware('feature:capacity-models');
        Route::put('workstreams/{workstream}/capacity-models', [CapacityModelController::class, 'updateForWorkstream'])->middleware('feature:capacity-models');
    });

    Route::middleware('role:company_admin,worker')->group(function () {
        Route::get('timesheet/worked-times', [TimeoffRequestController::class, 'workedTimes'])->middleware('feature:time-logging');
        Route::get('timelog/status', [TimelogController::class, 'status'])->middleware('feature:time-logging');
        Route::post('timelog/start', [TimelogController::class, 'start'])->middleware('feature:time-logging');
        Route::patch('timelog/stop', [TimelogController::class, 'stop'])->middleware('feature:time-logging');
        Route::post('timelog/break', [TimelogController::class, 'startBreak'])->middleware('feature:time-logging');
        Route::patch('timelog/resume', [TimelogController::class, 'resume'])->middleware('feature:time-logging');
    });

    Route::get('companies/{company}/owner-options', [CompanyController::class, 'ownerOptions']);
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('company-users', CompanyUserController::class);
    Route::put('company-users/{user}/seniorities', [CompanyUserSeniorityController::class, 'updateForUser'])
        ->middleware('role:company_admin');
    Route::apiResource('company-user-seniorities', CompanyUserSeniorityController::class);
    Route::apiResource('features', FeatureController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::apiResource('subscription-plans', SubscriptionPlanController::class);
    Route::apiResource('subscription-plan-features', SubscriptionPlanFeatureController::class);
    Route::apiResource('timeoff-requests', TimeoffRequestController::class)->middleware('feature:company-timeoff');
    Route::get('work-log/workstreams', [WorkLogController::class, 'workstreams']);
    Route::get('work-log', [WorkLogController::class, 'index']);
    Route::post('work-log', [WorkLogController::class, 'store']);
    Route::put('work-log/{id}', [WorkLogController::class, 'update']);
    Route::get('users/without-company', [UserController::class, 'withoutCompany']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('user-workstreams', UserWorkstreamController::class);
    Route::apiResource('workstreams', WorkstreamController::class);
});
