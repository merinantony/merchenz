<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class MerchantDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * GET /api/merchants/{id}/dashboard
     *
     * Returns:
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
        ]);
    }
}
