<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Models\AdminView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedViewController extends AdminController
{
    private const ROUTES = ['orders' => 'admin.orders.index', 'products' => 'admin.products.index', 'customers' => 'admin.customers.index'];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'resource' => ['required', Rule::in(AdminView::RESOURCES)],
            'name' => ['required', 'string', 'max:40'],
            'query' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->authorizeResource($request, $data['resource']);

        parse_str((string) ($data['query'] ?? ''), $query);
        $query = AdminView::normalize($query);
        unset($query['tab']);   // a saved view replaces the built-in tabs

        $view = AdminView::create(['resource' => $data['resource'], 'name' => $data['name'], 'query' => $query, 'admin_id' => $request->user('admin')->id]);

        return redirect()->route(self::ROUTES[$view->resource], $query + ['view' => $view->id])->with('success', __('admin.views.saved', ['name' => $view->name]));
    }

    public function destroy(Request $request, AdminView $view): RedirectResponse
    {
        $this->authorizeResource($request, $view->resource);
        $view->delete();

        return redirect()->route(self::ROUTES[$view->resource])->with('success', __('admin.views.deleted'));
    }

    private function authorizeResource(Request $request, string $resource): void
    {
        $area = AdminRole::areaForRoute(self::ROUTES[$resource]);
        abort_if($area && ! $request->user('admin')->mayAccess($area), 403);
    }
}
