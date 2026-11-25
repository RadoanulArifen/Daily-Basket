<?php

namespace App\Http\Controllers\Admin\Insights;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Traffic\TrafficReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Analytics → Traffic (area "analytics"). */
class TrafficController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.insights.traffic', ['report' => TrafficReport::for((string) $request->query('period', '30d'))]);
    }

    /** The "Live now" panel refreshes from here every 10 seconds. */
    public function live(): JsonResponse
    {
        $live = TrafficReport::live();

        return response()->json(['count' => $live['count'], 'live' => $live['rows'], 'empty' => __('admin_traffic.live_empty')])
            ->header('Cache-Control', 'no-store');
    }
}
