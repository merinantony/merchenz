<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    /**
     * Super Admin Global Monitoring Dashboard.
     * Displays all platform metrics, row-level tenant statistics, and all users across all tenants.
     */
    public function dashboard(): View
    {
        // Run with TenantScope disabled so super admin inspects everything
        return TenantScope::withoutTenancy(function () {
            $totalMerchants = Merchant::count();
            $totalUsers = User::count();
            $totalCustomers = Customer::count();
            $totalEvents = UsageEvent::count();

            $activeSubscriptions = Subscription::where('status', 'active')->with(['plan', 'customer'])->get();
            $totalMRR = $activeSubscriptions->sum(fn ($s) => (float) ($s->plan?->base_price ?? 0));

            // All merchants enriched with admin and plan
            $merchants = Merchant::with(['users', 'plans', 'customers'])->get()->map(function ($merchant) {
                $admin = $merchant->users->firstWhere('role', User::ROLE_ADMIN) ?? $merchant->users->first();
                $sub = Subscription::where('merchant_id', $merchant->id)->where('status', 'active')->first();
                $plan = $sub?->plan ?? $merchant->plans->first();
                $usageCount = UsageEvent::where('merchant_id', $merchant->id)->sum('units');

                return [
                    'id' => $merchant->id,
                    'company_name' => $merchant->company_name ?? $merchant->name,
                    'email' => $merchant->email,
                    'phone' => $merchant->phone ?? 'N/A',
                    'admin_name' => $admin?->name ?? 'None',
                    'admin_email' => $admin?->email ?? 'None',
                    'plan_name' => $plan?->name ?? 'Custom',
                    'base_price' => (float) ($plan?->base_price ?? 0),
                    'included_units' => (int) ($plan?->included_units ?? $plan?->included_usage_units ?? 0),
                    'total_usage' => (int) $usageCount,
                    'customers_count' => $merchant->customers->count(),
                    'created_at' => $merchant->created_at?->format('M d, Y') ?? 'N/A',
                ];
            });

            // All users across all companies (super admin can see all users data)
            $allUsers = User::with('merchant')->latest()->get();

            return view('superadmin.dashboard', [
                'totalMerchants' => $totalMerchants,
                'totalUsers' => $totalUsers,
                'totalCustomers' => $totalCustomers,
                'totalEvents' => $totalEvents,
                'totalMRR' => $totalMRR,
                'merchants' => $merchants,
                'allUsers' => $allUsers,
            ]);
        });
    }
}

