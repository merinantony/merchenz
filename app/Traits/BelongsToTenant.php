<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\Merchant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * BelongsToTenant Trait
 *
 * Automatically applies TenantScope (Row-Level Tenancy) to models and
 * automatically populates merchant_id on model creation if not explicitly provided.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait for a model.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (empty($model->merchant_id)) {
                if (TenantScope::$overrideTenantId !== null) {
                    $model->merchant_id = TenantScope::$overrideTenantId;
                } elseif (Auth::check() && Auth::user()?->merchant_id) {
                    $model->merchant_id = Auth::user()->merchant_id;
                } elseif (!empty($model->customer_id)) {
                    // Infer merchant_id from customer if not provided
                    $merchantId = Customer::withoutGlobalScopes()
                        ->where('id', $model->customer_id)
                        ->value('merchant_id');

                    if ($merchantId) {
                        $model->merchant_id = $merchantId;
                    }
                }
            }
        });
    }

    /**
     * The tenant/merchant this model belongs to.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}

