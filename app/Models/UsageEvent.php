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
            // Keep event_id and idempotency_key synchronized
            if ($event->isDirty('event_id') && !$event->isDirty('idempotency_key')) {
                $event->idempotency_key = $event->event_id;
            } elseif ($event->isDirty('idempotency_key') && !$event->isDirty('event_id')) {
                $event->event_id = $event->idempotency_key;
            } elseif (!empty($event->event_id) && empty($event->idempotency_key)) {
                $event->idempotency_key = $event->event_id;
            } elseif (!empty($event->idempotency_key) && empty($event->event_id)) {
                $event->event_id = $event->idempotency_key;
            }

            // Keep units and quantity synchronized
            if ($event->isDirty('quantity') && !$event->isDirty('units')) {
                $event->units = $event->quantity;
            } elseif ($event->isDirty('units') && !$event->isDirty('quantity')) {
                $event->quantity = $event->units;
            } elseif (isset($event->attributes['quantity']) && !isset($event->attributes['units'])) {
                $event->units = $event->attributes['quantity'];
            } elseif (isset($event->attributes['units']) && !isset($event->attributes['quantity'])) {
                $event->quantity = $event->attributes['units'];
            }

            // Keep timestamp and recorded_at synchronized
            if ($event->isDirty('timestamp') && !$event->isDirty('recorded_at')) {
                $event->recorded_at = $event->timestamp;
            } elseif ($event->isDirty('recorded_at') && !$event->isDirty('timestamp')) {
                $event->timestamp = $event->recorded_at;
            } elseif (!empty($event->timestamp) && empty($event->recorded_at)) {
                $event->recorded_at = $event->timestamp;
            } elseif (!empty($event->recorded_at) && empty($event->timestamp)) {
                $event->timestamp = $event->recorded_at;
            }
        });
    }

    public function getEventIdAttribute($value): ?string
    {
        return $value ?? ($this->attributes['idempotency_key'] ?? null);
    }

    public function getIdempotencyKeyAttribute($value): ?string
    {
        return $value ?? ($this->attributes['event_id'] ?? null);
    }

    public function getUnitsAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['quantity'] ?? 1));
    }

    public function getQuantityAttribute($value): int
    {
        return (int) ($value ?? ($this->attributes['units'] ?? 1));
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
