<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/demo', [AuthController::class, 'demo']);
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'update']);
        Route::delete('me', [AuthController::class, 'destroy']);

        Route::apiResource('categories', CategoryController::class);

        Route::get('transactions/export', [TransactionController::class, 'export']);
        Route::apiResource('transactions', TransactionController::class);

        Route::apiResource('goals', GoalController::class);
        Route::post('goals/{goal}/deposit', [GoalController::class, 'deposit']);

        Route::get('stats/summary', [StatsController::class, 'summary']);
        Route::get('stats/trend', [StatsController::class, 'trend']);
    });
});
