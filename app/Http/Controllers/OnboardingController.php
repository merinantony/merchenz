<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnboardingRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Show the tenant onboarding/registration page.
     */
    public function create(): View
    {
        // Plans catalog is available for selection
        $plans = TenantScope::withoutTenancy(function () {
            return Plan::where('is_active', true)->get();
        });

        // Fallback demo plans if database was freshly reset
        if ($plans->isEmpty()) {
            $plans = collect([
                (object) [
                    'id' => 1,
                    'name' => 'Starter Tier',
                    'base_price' => 49.00,
                    'billing_cycle_days' => 30,
                    'included_usage_units' => 10000,
                    'overage_rate_per_unit' => 0.0500,
                ],
                (object) [
                    'id' => 2,
                    'name' => 'Growth Tier',
                    'base_price' => 149.00,
                    'billing_cycle_days' => 30,
                    'included_usage_units' => 50000,
                    'overage_rate_per_unit' => 0.0200,
                ],
                (object) [
                    'id' => 3,
                    'name' => 'Enterprise Tier',
                    'base_price' => 499.00,
                    'billing_cycle_days' => 30,
                    'included_usage_units' => 250000,
                    'overage_rate_per_unit' => 0.0050,
                ],
            ]);
        }

        return view('onboarding', ['plans' => $plans]);
    }

    /**
     * Process tenant registration with Single Database Row-Level Tenancy:
     * 1. Register Merchant company in global database
     * 2. Create Tenant Admin User with merchant_id
     * 3. Attach chosen Plan and initialize active Subscription
     * 4. Log in and redirect to tenant dashboard
     */
    public function store(OnboardingRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            // 1. Create company record
            $merchant = Merchant::create([
                'name' => $request->company_name,
                'company_name' => $request->company_name,
                'email' => $request->company_email ?: $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'api_key' => 'live_mk_' . Str::random(32),
                'status' => 'active',
            ]);

            // 2. Create company admin user in Global DB
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_ADMIN,
                'merchant_id' => $merchant->id,
            ]);

            // 3. Attach selected plan with row-level merchant_id
            $selectedPlan = TenantScope::withoutTenancy(function () use ($request) {
                return Plan::find($request->plan_id);
            });

            // If selected plan belonged to another merchant or is global, create a tenant-scoped plan or subscribe
            $plan = Plan::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'name' => $selectedPlan?->name ?? 'Growth Tier',
                ],
                [
                    'base_price' => $selectedPlan?->base_price ?? 149.00,
                    'billing_cycle' => $selectedPlan?->billing_cycle ?? 30,
                    'billing_cycle_days' => $selectedPlan?->billing_cycle_days ?? 30,
                    'included_usage_units' => $selectedPlan?->included_usage_units ?? 50000,
                    'included_units' => $selectedPlan?->included_units ?? 50000,
                    'overage_rate_per_unit' => $selectedPlan?->overage_rate_per_unit ?? 0.0200,
                    'is_active' => true,
                ]
            );

            $cycleStart = Carbon::now()->startOfMonth();
            $cycleEnd = Carbon::now()->addDays($plan->billing_cycle_days ?: 30);

            $customer = Customer::create([
                'merchant_id' => $merchant->id,
                'name' => $merchant->company_name . ' Primary Account',
                'email' => $merchant->email,
            ]);

            Subscription::create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'started_at' => $cycleStart,
                'ends_at' => $cycleEnd,
                'status' => 'active',
            ]);

            // 4. Authenticate the newly registered admin
            Auth::login($user);

            return redirect()->route('merchant.dashboard', ['id' => $merchant->id])
                ->with('success', "Welcome to MerchenZ! {$merchant->company_name} is now active on the {$plan->name}.");
        });
    }
}
