<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserPlanController extends Controller
{
    public function __construct(
        protected readonly DashboardMetricsService $metricsService
    ) {}

    /**
     * Dedicated View for regular tenant users: "User can see his plan only".
     * Shows assigned plan details, included allowance, usage vs quota, and remaining units.
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // If super admin visits this page, redirect to super admin dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        $merchant = $user->merchant;
        $plan = $user->getActivePlan();

        // If merchant has active subscription
        $subscription = null;
        if ($merchant) {
            $subscription = Subscription::where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->first();
        }

        // If no active subscription was found, fallback to first plan
        if (!$plan && $subscription) {
            $plan = $subscription->plan;
        }

        if (!$plan) {
            $plan = Plan::where('merchant_id', $user->merchant_id)->first();
        }

        $includedUnits = $plan ? (int) ($plan->included_units ?: ($plan->included_usage_units ?: 0)) : 0;

        // Current cycle usage for this merchant
        $currentUsage = 0;
        if ($merchant) {
            $currentUsage = (int) UsageEvent::where('merchant_id', $merchant->id)
                ->whereBetween('recorded_at', [
                    Carbon::now()->startOfMonth()->startOfDay(),
                    Carbon::now()->endOfMonth()->endOfDay(),
                ])
                ->sum('units');

            if ($currentUsage === 0) {
                // Check through metrics service
                $cycleData = $this->metricsService->getCurrentCycleUsageVsAllowance($merchant->id);
                $currentUsage = (int) ($cycleData['usage_so_far'] ?? 0);
            }
        }

        $allowancePct = $includedUnits > 0
            ? round(($currentUsage / $includedUnits) * 100, 1)
            : 0.0;

        $remainingUnits = max(0, $includedUnits - $currentUsage);

        return view('user.plan', [
            'user' => $user,
            'merchant' => $merchant,
            'plan' => $plan,
            'subscription' => $subscription,
            'currentUsage' => $currentUsage,
            'includedUnits' => $includedUnits,
            'allowancePct' => $allowancePct,
            'remainingUnits' => $remainingUnits,
        ]);
    }
}

