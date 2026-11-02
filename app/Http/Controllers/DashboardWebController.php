<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardWebController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Show the merchant dashboard web view.
     */
    public function show(int $id): View
    {
        $dashboard = $this->dashboardService->getDashboard($id);

        return view('dashboard', ['dashboard' => $dashboard]);
    }
}
