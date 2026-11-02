<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DashboardMetricsController
 *
 * Exposes JSON API endpoints for merchant analytics:
 * - Current cycle usage vs allowance
 * - Top 5 customers by usage this cycle
 * - Projected overage revenue
 * - Churn Risk: Customers whose usage dropped >50% month-over-month
 */
class DashboardMetricsController extends Controller
{
    public function __construct(
        protected readonly DashboardMetricsService $metricsService
    ) {}

    /**
     * GET /api/merchants/{id}/metrics
     *
     * Returns JSON containing all four requested analytics metrics.
     */
    public function show(int $id): JsonResponse
    {
        $metrics = $this->metricsService->getMetrics($id);

        return response()->json([
            'merchant' => $metrics['merchant'],
            'current_cycle_usage_vs_allowance' => $metrics['current_cycle_usage_vs_allowance'],
            'top_customers' => $metrics['top_customers'],
            'projected_overage' => $metrics['projected_overage'],
            'churn_risk' => $metrics['churn_risk'],
            'system_status' => $metrics['system_status'] ?? [],
            'generated_at' => $metrics['generated_at'] ?? now()->toIso8601String(),
            'data' => $metrics,
        ]);
    }

    /**
     * GET /api/dashboard/metrics?merchant_id={id}
     *
     * Convenience route defaulting to merchant_id query parameter or first merchant.
     */
    public function index(Request $request): JsonResponse
    {
        $merchantId = (int) ($request->query('merchant_id') ?: Merchant::first()?->id ?: 1);

        return $this->show($merchantId);
    }
}

