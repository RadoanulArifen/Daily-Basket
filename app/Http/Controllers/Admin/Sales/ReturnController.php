<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\AdminController;
use App\Models\ReturnRequest;
use App\Services\Returns\ReturnService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnController extends AdminController
{
    public function index(Request $request, ReturnService $returns): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in([...ReturnRequest::STATUSES, 'open'])]])['status'] ?? 'open';

        return view('admin.sales.returns.index', [
            'returns' => ReturnRequest::query()->with('order')->withSum('items as units', 'quantity')
                ->when($status === 'open', fn ($q) => $q->whereIn('status', ['requested', 'approved', 'received']), fn ($q) => $q->where('status', $status))
                ->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'waiting' => ReturnRequest::query()->where('status', 'requested')->count(),
            'enabled' => $returns->enabled(),
            'days' => $returns->windowDays(),
        ]);
    }

    public function show(ReturnRequest $return): View
    {
        return view('admin.sales.returns.show', ['return' => $return->load(['order', 'items.orderItem'])]);
    }

    public function update(Request $request, ReturnRequest $return, ReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'receive', 'complete'])],
            'refund' => ['boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
            'restock' => ['nullable', 'boolean'],
        ]);
        $admin = $request->user('admin');

        match ($data['action']) {
            'approve' => $returns->approve($return, $data['note'] ?? null, $admin),
            'reject' => $returns->reject($return, $data['note'] ?? null, $admin),
            'receive' => $returns->receive($return, (bool) ($data['restock'] ?? false), $admin),
            'complete' => $returns->complete($return, $data['note'] ?? null, $admin, $request->boolean('refund')),
        };

        return back()->with('success', __("admin.returns.done.{$data['action']}"));
    }

    public function settings(Request $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validate(['window_days' => ['required', 'integer', 'min:0', 'max:365']]);
        $settings->setMany('returns', ['enabled' => $request->boolean('enabled'), 'window_days' => (int) $data['window_days']]);

        return back()->with('success', __('admin.settings.saved'));
    }
}
