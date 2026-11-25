<?php

// SECURITY: admin only (auth:admin + AuthorizeAdminArea); 'admin.customers.*' is mapped to the
// 'orders' area in AdminRole::AREAS (tested by tests/Feature/Admin/AdminRouteAreaMapTest.php).

namespace App\Http\Controllers\Admin\Insights;

use App\Http\Controllers\Admin\AdminController;
use App\Support\Search;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends AdminController
{
    public const SORTS = ['newest', 'spent', 'orders', 'recent'];

    public const SEGMENTS = ['all', 'returning', 'new', 'blocked'];

    public function index(\App\Http\Requests\Admin\Lists\CustomerListRequest $request): View
    {
        $this->authorizeArea('orders');
        $filters = $request->validated();
        $customers = app(\App\Services\Insights\CustomerQueryBuilder::class)->build($filters)
            ->paginate((int) config('store.pagination.admin_list', 50))
            ->withQueryString();

        return view('admin.insights.customers.index', ['customers' => $customers, 'filters' => $filters, 'total' => User::count()]);
    }

    public function show(User $customer): View
    {
        $this->authorizeArea('orders');
        $sales = $customer->orders()->countsAsSale();

        return view('admin.insights.customers.show', [
            'customer' => $customer->load('addresses'),
            'orders' => $customer->orders()->withCount('items')->limit((int) config('store.pagination.customer_orders', 25))->get(),
            'spent' => (float) (clone $sales)->sum('grand_total'),
            'salesCount' => (clone $sales)->count(),
            'wishlistCount' => $customer->wishlistItems()->count(),
            'reviewCount' => \App\Models\ProductReview::query()->where('user_id', $customer->id)->count(),
            'marketing' => $customer->orders()->where('marketing_opt_in', true)->exists(),
            'lastOrder' => $customer->orders()->with('items')->latest('id')->first(),
            'firstOrderAt' => $customer->orders()->min('placed_at'),
        ]);
    }

    /** Private staff notes about the customer. */
    public function note(Request $request, User $customer): RedirectResponse
    {
        $this->authorizeArea('orders');
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:2000']]);
        $customer->forceFill(['admin_note' => $data['admin_note'] ?? null])->save();

        return back()->with('success', __('admin.customers.note_saved'));
    }

    /** Block or unblock sign-in. Blocking also signs the customer out everywhere. */
    public function toggle(User $customer): RedirectResponse
    {
        $this->authorizeArea('orders');
        $customer->forceFill(['is_active' => ! $customer->is_active])->save();

        if (! $customer->is_active && config('session.driver') === 'database') {
            if (config('session.driver') === 'database') {   // only database sessions can be listed per customer
                DB::table(config('session.table', 'sessions'))->where('user_id', $customer->id)->delete();
            }
        }

        return back()->with('success', __($customer->is_active ? 'admin.customers.unblocked' : 'admin.customers.blocked'));
    }
}
