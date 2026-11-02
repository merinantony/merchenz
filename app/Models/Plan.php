<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'base_price',
        'billing_cycle',
        'billing_cycle_days',
        'included_usage_units',
        'included_units',
        'overage_rate_per_unit',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'overage_rate_per_unit' => 'decimal:4',
        'is_active' => 'boolean',
        'billing_cycle' => 'integer',
        'billing_cycle_days' => 'integer',
        'included_usage_units' => 'integer',
        'included_units' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Plan $plan) {
            // Keep included_usage_units and included_units synchronized
            if ($plan->included_usage_units !== null && ($plan->included_units === null || $plan->included_units === 0)) {
                $plan->included_units = $plan->included_usage_units;
            } elseif ($plan->included_units !== null && ($plan->included_usage_units === null || $plan->included_usage_units === 0)) {
                $plan->included_usage_units = $plan->included_units;
            }

            // Keep billing_cycle and billing_cycle_days synchronized
            if ($plan->billing_cycle !== null && ($plan->billing_cycle_days === null || $plan->billing_cycle_days === 0)) {
                $plan->billing_cycle_days = (int) $plan->billing_cycle;
            } elseif ($plan->billing_cycle_days !== null && ($plan->billing_cycle === null || $plan->billing_cycle === 0)) {
                $plan->billing_cycle = (int) $plan->billing_cycle_days;
            }
        });
    }

    public function getIncludedUsageUnitsAttribute(): int
    {
        return (int) ($this->attributes['included_usage_units'] ?? $this->attributes['included_units'] ?? 0);
    }

    public function getIncludedUnitsAttribute(): int
    {
        return (int) ($this->attributes['included_units'] ?? $this->attributes['included_usage_units'] ?? 0);
    }

    public function getBillingCycleAttribute(): int
    {
        return (int) ($this->attributes['billing_cycle'] ?? $this->attributes['billing_cycle_days'] ?? 30);
    }

    public function getBillingCycleDaysAttribute(): int
    {
        return (int) ($this->attributes['billing_cycle_days'] ?? $this->attributes['billing_cycle'] ?? 30);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
