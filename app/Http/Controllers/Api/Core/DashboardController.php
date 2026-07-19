<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenant();

        return response()->json([
            'tenant' => $tenant ? [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name ?? $tenant->getTenantKey(),
                'users_count' => User::where('tenant_id', $tenant->getTenantKey())->count(),
            ] : null,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'modules' => $this->getAvailableModules(),
            'quick_stats' => [
                'products' => 0,    // TODO: integrate with Inventory module
                'sales' => 0,       // TODO: integrate with Sales module
                'tasks' => 0,       // TODO: integrate with Projects module
            ],
        ]);
    }

    private function getAvailableModules(): array
    {
        return [
            ['id' => 'inventory', 'name' => __('modules.inventory'), 'icon' => 'inventory_2', 'enabled' => true],
            ['id' => 'financial', 'name' => __('modules.financial'), 'icon' => 'payments', 'enabled' => true],
            ['id' => 'sales', 'name' => __('modules.sales'), 'icon' => 'trending_up', 'enabled' => true],
            ['id' => 'purchasing', 'name' => __('modules.purchasing'), 'icon' => 'shopping_cart', 'enabled' => true],
            ['id' => 'equipment', 'name' => __('modules.equipment'), 'icon' => 'build', 'enabled' => true],
            ['id' => 'hr', 'name' => __('modules.hr'), 'icon' => 'people', 'enabled' => true],
            ['id' => 'projects', 'name' => __('modules.projects'), 'icon' => 'task_alt', 'enabled' => true],
            ['id' => 'reports', 'name' => __('modules.reports'), 'icon' => 'bar_chart', 'enabled' => true],
            ['id' => 'marketplace', 'name' => __('modules.marketplace'), 'icon' => 'store', 'enabled' => false],
            ['id' => 'lowcode', 'name' => __('modules.lowcode'), 'icon' => 'code', 'enabled' => false],
        ];
    }
}
