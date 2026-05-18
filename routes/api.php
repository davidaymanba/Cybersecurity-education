<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\Api\ProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/ai/chat', AiChatController::class)->middleware('throttle:20,1');
    Route::post('/progress', ProgressController::class);
    Route::get('/analytics', AnalyticsController::class)->middleware('admin');
    Route::get('/dashboard', UserDashboardController::class);
});
