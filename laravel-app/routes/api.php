<?php

use App\Http\Controllers\Api\AnalyticsApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'active'])->prefix('api')->group(function () {
        Route::get('/enrollment', [AnalyticsApiController::class, 'enrollment']);
        Route::get('/enrollment/trends', [AnalyticsApiController::class, 'trends']);
        Route::get('/enrollment/programs', [AnalyticsApiController::class, 'programs']);
        Route::get('/enrollment/colleges', [AnalyticsApiController::class, 'colleges']);
        Route::get('/enrollment/retention', [AnalyticsApiController::class, 'retention']);
        Route::get('/analytics/summary', [AnalyticsApiController::class, 'summary']);
        Route::match(['get', 'post'], '/forecast', [AnalyticsApiController::class, 'forecast']);
        Route::match(['get', 'post'], '/ai/insights', [AnalyticsApiController::class, 'insights']);
        Route::get('/alerts', [AnalyticsApiController::class, 'alerts']);
    });
});
