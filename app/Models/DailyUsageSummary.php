<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUsageSummary extends Model
{
    use HasFactory;

    protected $table = 'daily_usage_summaries';

    protected $fillable = [
        'customer_id',
        'usage_date',
        'total_quantity',
        'total_units',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'total_quantity' => 'integer',
        'total_units' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (DailyUsageSummary $summary) {
            // Keep total_units and total_quantity synchronized
            if ($summary->total_units !== null && ($summary->total_quantity === null || $summary->total_quantity === 0)) {
                $summary->total_quantity = $summary->total_units;
            } elseif ($summary->total_quantity !== null && ($summary->total_units === null || $summary->total_units === 0)) {
                $summary->total_units = $summary->total_quantity;
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
