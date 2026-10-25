<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetrics;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The start dashboard: KPI cards, charts and recent activity. All figures are
 * read-only aggregations provided by {@see DashboardMetrics}.
 */
class DashboardController extends Controller {
    public function __invoke(DashboardMetrics $metrics): Response {
        return Inertia::render('Dashboard', [
            'kpis' => $metrics->kpis(),
            'ordersByMonth' => $metrics->ordersByMonth(),
            'ordersByStatus' => $metrics->ordersByStatus(),
            'lowStock' => $metrics->lowStock(),
            'activity' => $metrics->recentActivity(),
        ]);
    }
}
