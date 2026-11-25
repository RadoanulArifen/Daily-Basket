<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\AdminController;
use App\Models\CustomOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Made-to-order enquiries from the storefront: the list, one enquiry, and its status. */
class EnquiryController extends AdminController
{
    public const STATUSES = ['new', 'quoted', 'won', 'closed'];

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : 'new';
        $term = trim((string) $request->query('q'));

        $enquiries = CustomOrderRequest::query()
            ->with('product:id,name,slug')
            ->where('status', $status)
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%")))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.sales.enquiries.index', [
            'enquiries' => $enquiries,
            'status' => $status,
            'term' => $term,
            'counts' => CustomOrderRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(CustomOrderRequest $enquiry): View
    {
        return view('admin.sales.enquiries.show', [
            'enquiry' => $enquiry->load('product:id,name,slug'),
        ]);
    }

    public function update(Request $request, CustomOrderRequest $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $enquiry->forceFill($data)->save();

        return back()->with('success', __('admin.enquiries.saved'));
    }
}
