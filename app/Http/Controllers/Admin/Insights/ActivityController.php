<?php

namespace App\Http\Controllers\Admin\Insights;

use App\Support\Search;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends AdminController
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'string', 'max:60'],
            'admin' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.insights.activity', [
            'logs' => AuditLog::query()->with('admin:id,name')
                ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('subject_type', $t))
                ->when($filters['admin'] ?? null, fn ($q, $a) => $q->where('admin_id', $a))
                ->when($filters['q'] ?? null, fn ($q, $t) => Search::like($q, 'subject_label', $t))
                ->latest('id')->paginate(50)->withQueryString(),
            'types' => AuditLog::query()->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'admins' => Admin::query()->orderBy('name')->pluck('name', 'id'),
            'filters' => $filters,
        ]);
    }
}
