<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * TenantScope
 *
 * Laravel Global Scope implementing Single Database Row-Level Tenancy.
 * Restricts queries to the authenticated tenant's merchant_id:
 * - Super Admins bypass this scope and see all data across all tenants.
 * - Tenant Admins and Users are restricted to their company's data.
 * - Unauthenticated CLI/unit tests run without restriction unless explicitly overridden.
 */
class TenantScope implements Scope
{
    /**
     * Explicit tenant ID override for background jobs or explicit multi-tenant switching.
     */
    public static ?int $overrideTenantId = null;

    /**
     * Global bypass toggle (e.g. for super admin aggregations or platform migrations).
     */
    public static bool $disabled = false;

    /**
     * Apply the tenant scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (static::$disabled) {
            return;
        }

        // 1. Explicit tenant override (e.g. running in job or sub-context)
        if (static::$overrideTenantId !== null) {
            $builder->where($model->qualifyColumn('merchant_id'), static::$overrideTenantId);
            return;
        }

        // 2. Authenticated user-based tenant scoping
        if (Auth::check()) {
            $user = Auth::user();

            // Super Admin can see all users, all companies, and all rows
            if ($user instanceof User && $user->isSuperAdmin()) {
                return;
            }

            // Tenant Admins and Regular Users only see rows for their merchant_id
            if ($user instanceof User && $user->merchant_id) {
                $builder->where($model->qualifyColumn('merchant_id'), $user->merchant_id);
                return;
            }
        }
    }

    /**
     * Run a callback explicitly scoped to a specific tenant ID.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function forTenant(int $tenantId, callable $callback)
    {
        $previous = static::$overrideTenantId;
        static::$overrideTenantId = $tenantId;

        try {
            return $callback();
        } finally {
            static::$overrideTenantId = $previous;
        }
    }

    /**
     * Run a callback with tenant scoping disabled (e.g. for super admin platform reporting).
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function withoutTenancy(callable $callback)
    {
        $previous = static::$disabled;
        static::$disabled = true;

        try {
            return $callback();
        } finally {
            static::$disabled = $previous;
        }
    }
}

