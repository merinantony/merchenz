<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUsageSummary extends Model
{
    protected $fillable = [
        'customer_id',
        'usage_date',
        'total_quantity',
    ];

    protected $casts = [
        'usage_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
