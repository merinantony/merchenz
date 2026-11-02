<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUsageAggregation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'daily_usage_aggregations';

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'usage_date',
        'total_units',
        'total_quantity',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'total_units' => 'integer',
        'total_quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (DailyUsageAggregation $aggregation) {
            // Keep total_units and total_quantity synchronized
            if ($aggregation->total_units !== null && ($aggregation->total_quantity === null || $aggregation->total_quantity === 0)) {
                $aggregation->total_quantity = $aggregation->total_units;
            } elseif ($aggregation->total_quantity !== null && ($aggregation->total_units === null || $aggregation->total_units === 0)) {
                $aggregation->total_units = $aggregation->total_quantity;
            }
        });
    }

    public function getTotalUnitsAttribute(): int
    {
        return (int) ($this->attributes['total_units'] ?? $this->attributes['total_quantity'] ?? 0);
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) ($this->attributes['total_quantity'] ?? $this->attributes['total_units'] ?? 0);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

