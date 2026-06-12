<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Money;
use App\Support\ShopMetrics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The five numbers, for 30 days and 7 days.
     */
    public function __invoke(ShopMetrics $metrics): Response
    {
        $format = fn (array $period): array => [
            'revenue' => Money::format($period['revenue']),
            'orders' => $period['orders'],
            'aov' => Money::format($period['aov']),
            'abandonment_rate' => $period['abandonment_rate'],
            'repeat_rate' => $period['repeat_rate'],
        ];

        return Inertia::render('admin/dashboard', [
            'month' => $format($metrics->forPeriod(now()->subDays(30))),
            'week' => $format($metrics->forPeriod(now()->subDays(7))),
        ]);
    }
}
