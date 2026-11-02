<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'merchant_id',
        'customer_id',
        'plan_id',
        'started_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(SubscriptionChange::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if this subscription's billing cycle has ended.
     */
    public function isCycleEnded(): bool
    {
        return now()->gte($this->ends_at);
    }

    /**
     * Get total cycle days.
     */
    public function getCycleDays(): int
    {
        return max(1, (int) \Carbon\Carbon::parse($this->started_at->toDateString())
            ->diffInDays(\Carbon\Carbon::parse($this->ends_at->toDateString())));
    }
}
