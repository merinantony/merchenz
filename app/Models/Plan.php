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
            if ($plan->isDirty('included_units') && !$plan->isDirty('included_usage_units')) {
                $plan->included_usage_units = $plan->included_units;
            } elseif ($plan->isDirty('included_usage_units') && !$plan->isDirty('included_units')) {
                $plan->included_units = $plan->included_usage_units;
            } elseif ($plan->included_units && !$plan->included_usage_units) {
                $plan->included_usage_units = $plan->included_units;
            } elseif ($plan->included_usage_units && !$plan->included_units) {
                $plan->included_units = $plan->included_usage_units;
            }

            // Keep billing_cycle and billing_cycle_days synchronized
            if ($plan->isDirty('billing_cycle_days') && !$plan->isDirty('billing_cycle')) {
                $plan->billing_cycle = $plan->billing_cycle_days;
            } elseif ($plan->isDirty('billing_cycle') && !$plan->isDirty('billing_cycle_days')) {
                $plan->billing_cycle_days = $plan->billing_cycle;
            } elseif ($plan->billing_cycle_days && !$plan->billing_cycle) {
                $plan->billing_cycle = $plan->billing_cycle_days;
            } elseif ($plan->billing_cycle && !$plan->billing_cycle_days) {
                $plan->billing_cycle_days = $plan->billing_cycle;
            }
        });
    }

    public function getIncludedUsageUnitsAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['included_units'] ?? 0));
    }

    public function getIncludedUnitsAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['included_usage_units'] ?? 0));
    }

    public function getBillingCycleAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['billing_cycle_days'] ?? 30));
    }

    public function getBillingCycleDaysAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['billing_cycle'] ?? 30));
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
