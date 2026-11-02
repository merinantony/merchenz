<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;

class MerchantDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $dashboardService
    ) {}

    /**
     * GET /api/merchants/{id}/dashboard
     *
     * Returns JSON for:
     * - Current cycle usage vs allowance
     * - Top 5 customers by usage this month
     * - Projected overage revenue for the current cycle
     * - Customers whose usage dropped >50% MoM (churn risk)
     * - System status metadata
     */
    public function show(int $id): JsonResponse
    {
        $dashboard = $this->dashboardService->getDashboard($id);

        return response()->json([
            'data' => $dashboard,
            'merchant' => $dashboard['merchant'],
            'current_cycle_usage_vs_allowance' => $dashboard['current_cycle_usage_vs_allowance'],
            'top_customers' => $dashboard['top_customers'],
            'projected_overage' => $dashboard['projected_overage'],
            'churn_risk' => $dashboard['churn_risk'],
            'system_status' => $dashboard['system_status'] ?? [],
            'generated_at' => $dashboard['generated_at'] ?? now()->toIso8601String(),
        ]);
    }
}
