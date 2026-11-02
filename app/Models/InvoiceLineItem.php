<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'plan_id',
        'segment_start',
        'segment_end',
        'base_amount',
        'usage_units',
        'included_units',
        'overage_units',
        'overage_amount',
        'line_total',
        'description',
    ];

    protected $casts = [
        'segment_start' => 'date',
        'segment_end' => 'date',
        'base_amount' => 'decimal:2',
        'overage_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
