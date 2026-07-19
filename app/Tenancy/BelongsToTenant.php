<?php

declare(strict_types=1);

namespace App\Tenancy;

/**
 * Apply this trait to any Eloquent model that belongs to a tenant.
 *
 * Requires a `tenant_id` column on the model's table.
 * Automatically scopes all queries to the current tenant.
 *
 * Usage:
 *   class Product extends Model
 *   {
 *       use BelongsToTenant;
 *   }
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(app(TenantScope::class));

        static::creating(function ($model) {
            if (! $model->tenant_id && tenant()) {
                $model->tenant_id = tenant()->getTenantKey();
            }
        });
    }

    /**
     * Remove the tenant scope for a single query.
     *
     * Example: Product::withoutTenancy()->get()
     */
    public static function withoutTenancy()
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
