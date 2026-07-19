<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the current tenant from the request and initialize tenancy.
 *
 * Tenant is identified by the X-Tenant header (tenant UUID or domain).
 * Fallback: the first segment of the URL path can be used as tenant identifier.
 */
class ResolveTenant
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                $this->tenancy->initialize($tenant);
            }
        }

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?string
    {
        // 1. X-Tenant header (primary method for API)
        if ($request->hasHeader('X-Tenant')) {
            return $request->header('X-Tenant');
        }

        // 2. X-Tenant-ID header (alternative)
        if ($request->hasHeader('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }

        // 3. Bearer token tenant claim (for Sanctum-authenticated users)
        // The tenant can be embedded in the token abilities or as a custom claim
        // Handled separately in auth middleware

        // 4. Subdomain (e.g., tenant1.erpuniversal.com)
        // Handled by stancl/tenancy domain identification

        return null;
    }
}
