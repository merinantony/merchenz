<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'merchant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The company/merchant tenant this user belongs to (null for super_admin).
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Check if user is Super Admin (can monitor and view all tenants).
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is Tenant/Company Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is a standard tenant user.
     */
    public function isRegularUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Get the plan assigned to this user's company/subscription.
     */
    public function getActivePlan(): ?Plan
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $merchant = $this->merchant;
        if (!$merchant) {
            return null;
        }

        // Check active subscription linked to any customer or merchant
        $sub = Subscription::query()
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchant->id)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.*')
            ->first();

        return $sub?->plan ?? $merchant->plans()->first();
    }
}
