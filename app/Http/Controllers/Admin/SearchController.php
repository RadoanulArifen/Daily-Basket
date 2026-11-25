<?php

namespace App\Http\Controllers\Admin;

use App\Support\Search;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Top-bar search (⌘K): orders, products and customers the admin's role may see. */
class SearchController extends AdminController
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        $admin = $request->user('admin');
        $groups = [];

        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.mb_strtolower($term).'%';
        $digits = ltrim(preg_replace('/\D/', '', $term), '0');
        $tz = config('store.display_timezone');

        if ($admin->mayAccess('orders')) {
            $number = strtoupper(preg_replace('/\s+/', '', $term));
            $orders = Order::query()
                ->where(fn ($q) => $q->where('number', 'like', '%'.$number.'%')
                    ->orWhereRaw('lower(email) like ?', [$like])
                    ->orWhereRaw('lower(ship_name) like ?', [$like])
                    ->when(strlen($digits) >= 5, fn ($p) => $p->orWhere('phone', 'like', '%'.$digits.'%')))
                ->latest('id')->limit(5)->get(['id', 'number', 'ship_name', 'grand_total', 'currency', 'placed_at', 'status']);

            $groups[] = ['label' => __('admin.nav.orders'), 'items' => $orders->map(fn (Order $o) => [
                'title' => $o->number,
                'meta' => $o->ship_name.' · '.price_admin($o->grand_total).' · '.$o->placed_at->timezone($tz)->translatedFormat('j M'),
                'url' => route('admin.orders.show', $o),
                'icon' => 'shopping-bag',
            ])->all()];

            $customers = User::query()
                ->where(fn ($q) => Search::like($q, 'name', $term)->orWhereRaw('lower(email) like ?', [$like])
                    ->when(strlen($digits) >= 5, fn ($p) => $p->orWhere('phone', 'like', '%'.$digits.'%')))
                ->latest('id')->limit(5)->get(['id', 'name', 'email']);

            $groups[] = ['label' => __('admin.nav.customers'), 'items' => $customers->map(fn (User $u) => [
                'title' => $u->name, 'meta' => $u->email, 'url' => route('admin.customers.show', $u), 'icon' => 'users',
            ])->all()];
        }

        if ($admin->mayAccess('catalog')) {
            $products = Product::query()
                ->where(fn ($q) => Search::like($q, 'name->en', $term)
                    ->pipe(fn ($inner) => Search::like($inner, 'name->ar', $term, or: true))
                    ->orWhereHas('variants', fn ($v) => Search::like($v, 'sku', $term)))
                ->with('media')->latest('id')->limit(5)->get(['id', 'name', 'status']);

            $groups[] = ['label' => __('admin.nav.products'), 'items' => $products->map(fn (Product $p) => [
                'title' => $p->name,
                'meta' => $p->status->label(),
                'url' => route('admin.products.edit', $p),
                'image' => $p->media->first()?->url('thumb'),
                'icon' => 'package',
            ])->all()];
        }

        return response()->json(['groups' => array_values(array_filter($groups, fn ($g) => $g['items']))]);
    }
}
