<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Core\DashboardController;
use App\Http\Controllers\Api\Core\LocaleController;
use App\Http\Controllers\Api\Core\TenantController;
use App\Http\Controllers\Api\Core\UserController;
use Illuminate\Support\Facades\Route;

// ── Dashboard ───────────────────────────────────────────────────────────
Route::get('dashboard', [DashboardController::class, 'index']);

// ── Locale / i18n ───────────────────────────────────────────────────────
Route::get('locales', [LocaleController::class, 'available']);
Route::put('locale', [LocaleController::class, 'switch']);

// ── Tenant settings (admin only) ─────────────────────────────────────────
Route::prefix('tenant')->group(function (): void {
    Route::get('/', [TenantController::class, 'show']);
    Route::put('/', [TenantController::class, 'update']);
    Route::get('settings', [TenantController::class, 'settings']);
    Route::put('settings', [TenantController::class, 'updateSettings']);
});

// ── User management (admin only) ─────────────────────────────────────────
Route::apiResource('users', UserController::class)->only([
    'index', 'store', 'show', 'update', 'destroy',
]);
Route::put('users/{user}/role', [UserController::class, 'updateRole']);
Route::post('users/{user}/invite', [UserController::class, 'invite']);

// ── Roles & Permissions (admin only) ─────────────────────────────────────
Route::get('roles', [UserController::class, 'roles']);
Route::get('permissions', [UserController::class, 'permissions']);
