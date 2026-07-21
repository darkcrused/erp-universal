<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// ── Public auth routes ──────────────────────────────────────────────────
Route::get('login', fn () => response()->json([
    'message' => 'Use POST /api/auth/login',
    'fields' => ['email', 'password', 'tenant_id?'],
]));
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// ── Protected auth routes ───────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::put('password', [AuthController::class, 'updatePassword']);
    Route::post('enable-2fa', [AuthController::class, 'enableTwoFactor']);
    Route::post('disable-2fa', [AuthController::class, 'disableTwoFactor']);
});
