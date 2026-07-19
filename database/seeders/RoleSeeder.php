<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ──────────────────────────────────────────────
        $permissions = [
            // Core
            'core.dashboard.view',
            'core.users.view', 'core.users.create', 'core.users.edit', 'core.users.delete',
            'core.roles.view', 'core.roles.edit',
            'core.tenant.settings',

            // Inventory
            'inventory.products.view', 'inventory.products.create', 'inventory.products.edit', 'inventory.products.delete',
            'inventory.categories.view', 'inventory.categories.create', 'inventory.categories.edit', 'inventory.categories.delete',
            'inventory.movements.view', 'inventory.movements.create',
            'inventory.warehouses.view', 'inventory.warehouses.create', 'inventory.warehouses.edit', 'inventory.warehouses.delete',

            // Financial
            'financial.accounts.view', 'financial.accounts.create', 'financial.accounts.edit', 'financial.accounts.delete',
            'financial.entries.view', 'financial.entries.create', 'financial.entries.edit', 'financial.entries.delete',
            'financial.reports.view', 'financial.reports.export',

            // Sales
            'sales.pipeline.view', 'sales.pipeline.manage',
            'sales.orders.view', 'sales.orders.create', 'sales.orders.edit', 'sales.orders.delete',
            'sales.invoices.view', 'sales.invoices.create',
            'sales.clients.view', 'sales.clients.create', 'sales.clients.edit', 'sales.clients.delete',

            // Purchasing
            'purchasing.orders.view', 'purchasing.orders.create', 'purchasing.orders.edit', 'purchasing.orders.delete',
            'purchasing.suppliers.view', 'purchasing.suppliers.create', 'purchasing.suppliers.edit', 'purchasing.suppliers.delete',

            // Equipment
            'equipment.assets.view', 'equipment.assets.create', 'equipment.assets.edit', 'equipment.assets.delete',
            'equipment.maintenance.view', 'equipment.maintenance.create', 'equipment.maintenance.edit',

            // HR
            'hr.employees.view', 'hr.employees.create', 'hr.employees.edit', 'hr.employees.delete',
            'hr.payroll.view', 'hr.payroll.process',

            // Projects
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
            'projects.tasks.view', 'projects.tasks.create', 'projects.tasks.edit', 'projects.tasks.delete',

            // Reports
            'reports.view', 'reports.create', 'reports.export',

            // Marketplace
            'marketplace.browse', 'marketplace.install', 'marketplace.publish',

            // LowCode
            'lowcode.forms.edit', 'lowcode.workflows.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Roles ────────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions(Permission::whereNotIn('name', [
            'core.users.delete', 'core.roles.edit', 'core.tenant.settings',
            'marketplace.install', 'marketplace.publish',
            'lowcode.forms.edit', 'lowcode.workflows.edit',
        ])->get());

        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->syncPermissions(Permission::whereIn('name', [
            'core.dashboard.view',
            'inventory.products.view', 'inventory.categories.view', 'inventory.movements.view',
            'financial.accounts.view', 'financial.entries.view', 'financial.reports.view',
            'sales.orders.view', 'sales.invoices.view', 'sales.clients.view',
            'purchasing.orders.view', 'purchasing.suppliers.view',
            'equipment.assets.view', 'equipment.maintenance.view',
            'hr.employees.view',
            'projects.view', 'projects.tasks.view',
            'reports.view',
            'marketplace.browse',
        ])->get());

        $readonly = Role::firstOrCreate(['name' => 'readonly', 'guard_name' => 'web']);
        $readonly->syncPermissions(Permission::where('name', 'like', '%.view')->get());
    }
}
