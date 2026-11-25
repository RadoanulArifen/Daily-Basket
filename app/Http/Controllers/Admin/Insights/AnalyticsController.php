<?php

namespace App\Http\Controllers\Admin\Insights;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Analytics\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyticsController extends AdminController
{
    public function __invoke(Request $request, SalesReport $report): View
    {
        $days = (int) ($request->validate(['days' => ['nullable', Rule::in(SalesReport::RANGES)]])['days'] ?? 30);

        $funnel = app(\App\Services\Analytics\Funnel::class);

        return view('admin.insights.analytics', [
            'report' => $report->build($days),
            'funnel' => $funnel->report($days),
            'missedSearches' => $funnel->missedSearches(),
        ]);
    }
}
