<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Tenancy;

/**
 * Global scope that automatically filters queries by the current tenant.
 * Apply this trait to any model that belongs to a tenant.
 *
 * Requires a tenant_id column on the model's table.
 */
class TenantScope implements Scope
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        if (! $this->tenancy->initialized) {
            return;
        }

        /** @var Tenant $tenant */
        $tenant = tenant();

        if (! $tenant) {
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', $tenant->getTenantKey());
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenancy', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
