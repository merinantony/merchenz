<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'database_name',
        'status',
        'api_key',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the tenant admin user for this company.
     */
    public function adminUser(): ?User
    {
        return $this->users()->where('role', User::ROLE_ADMIN)->first() ?? $this->users()->first();
    }

    /**
     * Get active subscription across this merchant's customers.
     */
    public function getActiveSubscriptionAttribute(): ?Subscription
    {
        return Subscription::query()
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $this->id)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.*')
            ->first();
    }
}
