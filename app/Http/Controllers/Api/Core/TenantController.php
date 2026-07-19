<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stancl\Tenancy\Contracts\Tenant;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    /**
     * Show current tenant info.
     */
    public function show(): JsonResponse
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return response()->json(['message' => __('core.no_tenant')], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name ?? $tenant->getTenantKey(),
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ],
        ]);
    }

    /**
     * Update tenant info.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var Tenant $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return response()->json(['message' => __('core.no_tenant')], Response::HTTP_NOT_FOUND);
        }

        $tenant->update(['name' => $request->name]);

        return response()->json([
            'message' => __('core.tenant_updated'),
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name,
                'updated_at' => $tenant->fresh()->updated_at,
            ],
        ]);
    }

    /**
     * Get tenant settings (JSON metadata stored in tenant model).
     */
    public function settings(): JsonResponse
    {
        /** @var Tenant|null $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return response()->json(['message' => __('core.no_tenant')], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'settings' => $tenant->data ?? [],
        ]);
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.company_name' => 'nullable|string|max:255',
            'settings.company_document' => 'nullable|string|max:30',
            'settings.company_email' => 'nullable|email|max:255',
            'settings.company_phone' => 'nullable|string|max:30',
            'settings.default_locale' => 'nullable|string|in:pt_BR,en,es',
            'settings.default_timezone' => 'nullable|string|max:50',
            'settings.default_currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var Tenant $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return response()->json(['message' => __('core.no_tenant')], Response::HTTP_NOT_FOUND);
        }

        $tenant->update([
            'data' => array_merge((array) ($tenant->data ?? []), $request->settings),
        ]);

        return response()->json([
            'message' => __('core.settings_updated'),
            'settings' => $tenant->fresh()->data,
        ]);
    }
}
