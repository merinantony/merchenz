<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'event_id',
        'idempotency_key',
        'units',
        'quantity',
        'event_type',
        'timestamp',
        'recorded_at',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'recorded_at' => 'datetime',
        'units' => 'integer',
        'quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (UsageEvent $event) {
            // Keep event_id and idempotency_key synchronized for idempotency guarantees
            if (!empty($event->event_id) && empty($event->idempotency_key)) {
                $event->idempotency_key = $event->event_id;
            } elseif (!empty($event->idempotency_key) && empty($event->event_id)) {
                $event->event_id = $event->idempotency_key;
            }

            // Keep units and quantity synchronized
            if (isset($event->units) && !isset($event->quantity)) {
                $event->quantity = $event->units;
            } elseif (isset($event->quantity) && !isset($event->units)) {
                $event->units = $event->quantity;
            }

            // Keep timestamp and recorded_at synchronized
            if (!empty($event->timestamp) && empty($event->recorded_at)) {
                $event->recorded_at = $event->timestamp;
            } elseif (!empty($event->recorded_at) && empty($event->timestamp)) {
                $event->timestamp = $event->recorded_at;
            }
        });
    }

    public function getEventIdAttribute(): ?string
    {
        return $this->attributes['event_id'] ?? $this->attributes['idempotency_key'] ?? null;
    }

    public function getIdempotencyKeyAttribute(): ?string
    {
        return $this->attributes['idempotency_key'] ?? $this->attributes['event_id'] ?? null;
    }

    public function getUnitsAttribute(): int
    {
        return (int) ($this->attributes['units'] ?? $this->attributes['quantity'] ?? 1);
    }

    public function getQuantityAttribute(): int
    {
        return (int) ($this->attributes['quantity'] ?? $this->attributes['units'] ?? 1);
    }

    public function getTimestampAttribute($value)
    {
        $val = $value ?? ($this->attributes['recorded_at'] ?? null);
        return $val ? $this->asDateTime($val) : null;
    }

    public function getRecordedAtAttribute($value)
    {
        $val = $value ?? ($this->attributes['timestamp'] ?? null);
        return $val ? $this->asDateTime($val) : null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
