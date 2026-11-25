<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;

class DashboardController extends AdminController
{
    public function __invoke(): View
    {
        $setupSteps = app(\App\Services\Dashboard\SetupStepsBuilder::class)->build();

        $admin = request()->user('admin');

        return view('admin.dashboard.index', [
            'setupSteps' => $setupSteps,
            'completedSteps' => collect($setupSteps)->where('done', true)->count(),
            'report' => $admin->mayAccess('orders') ? app(\App\Services\Analytics\SalesReport::class)->build((int) config('store.dashboard.report_days', 30)) : null,
            'todos' => app(\App\Services\Dashboard\TodosBuilder::class)->build($admin),
            'recentOrders' => $admin->mayAccess('orders') ? \App\Models\Order::query()->latest('id')->limit(6)->get() : collect(),
        ]);
    }
}
