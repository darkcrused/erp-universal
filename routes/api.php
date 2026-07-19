<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — ERP Universal
|--------------------------------------------------------------------------
|
| Todas as rotas são prefixadas com /api e têm o middleware 'api' aplicado.
| O middleware ResolveTenant é executado automaticamente em todas as rotas.
|
*/

// ── Health check (public) ──────────────────────────────────────────────
Route::get('ping', fn () => response()->json([
    'app' => config('app.name'),
    'version' => '1.0.0',
    'timestamp' => now()->toIso8601String(),
    'locales' => config('app.available_locales', []),
]));

// ── Auth routes (public) ────────────────────────────────────────────────
Route::prefix('auth')->group(base_path('routes/api/auth.php'));

// ── Core module (requires auth + tenant) ────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function (): void {
    // Core: User profile, tenant settings, dashboard
    Route::prefix('core')->group(base_path('routes/api/core.php'));

    // Each ERP module registers its routes via service provider
    // Modules are discovered and loaded dynamically
});

// ── Fallback for undefined routes ──────────────────────────────────────
Route::fallback(function (Request $request) {
    return response()->json([
        'message' => __('http-statuses.404'),
        'path' => $request->path(),
        'method' => $request->method(),
    ], 404);
});
