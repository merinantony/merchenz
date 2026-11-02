<?php

namespace App\Services;

/**
 * DashboardService
 *
 * Extends DashboardMetricsService for backward compatibility with existing controllers,
 * web dashboard views, and test suites.
 */
class DashboardService extends DashboardMetricsService
{
    // Inherits all metrics, calculations, and caching methods from DashboardMetricsService
}
