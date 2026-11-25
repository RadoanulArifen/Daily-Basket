<?php

// SECURITY: admin only (auth:admin + AuthorizeAdminArea, area: orders — 'admin.draft-orders.*');
// saving, deleting and placing a draft also check mayAccess('orders') in the action.

namespace App\Http\Controllers\Admin\Sales;

use App\Support\Search;
use App\Http\Controllers\Admin\AdminController;
use App\Actions\Checkout\PlaceOrder;
use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Orders\OrderService;
use App\Services\Shipping\ShippingService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * "Create order" for phone, WhatsApp and in-person sales. The chosen items go through the
 * same PlaceOrder action as the website checkout, so stock, VAT, deals, discount codes and
 * order numbers work exactly the same.
 */
class DraftOrderController extends AdminController
{
    public function create(Request $request): View
    {
        $this->authorizeArea('orders');
        // ?draft=12 picks up where the team left off.
        $draft = $request->filled('draft')
            ? \App\Models\OrderDraft::query()->find($request->integer('draft'))
            : null;

        return view('admin.sales.orders.create', ['draft' => $draft]);
    }

    /** The saved, unplaced orders. */
    public function drafts(): View
    {
        $this->authorizeArea('orders');
        return view('admin.sales.orders.drafts', [
            'drafts' => \App\Models\OrderDraft::query()->with('admin:id,name')->latest('updated_at')->paginate(25),
        ]);
    }

    /**
     * Save what has been entered so far, without placing anything.
     *
     * A draft is deliberately not an order: it has no number, holds no stock and appears in
     * no report until it is actually placed.
     */
    public function saveDraft(Request $request): RedirectResponse
    {
        $this->authorizeArea('orders');
        $data = $request->validate([
            'draft_id' => ['nullable', 'integer', Rule::exists('order_drafts', 'id')],
            'lines' => ['nullable', 'array', 'max:'.(int) config('store.draft_order_max_lines', 100)],
            'lines.*' => ['integer', 'min:1', 'max:999'],
            'customer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
            'shipping_method' => ['nullable', 'string', 'max:60'],
            'discount_code' => ['nullable', 'string', 'max:40'],
            'payment' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'locale' => ['nullable', 'string', 'max:5'],
        ]);

        $draft = app(\App\Services\Orders\DraftOrderService::class)->save($data, $request->user('admin'));

        return redirect()->route('admin.draft-orders.create', ['draft' => $draft->id])
            ->with('success', __('admin.draft.saved'));
    }

    public function destroyDraft(\App\Models\OrderDraft $draft): RedirectResponse
    {
        $this->authorizeArea('orders');
        app(\App\Services\Orders\DraftOrderService::class)->discard($draft);

        return redirect()->route('admin.draft-orders.drafts')->with('success', __('admin.draft.deleted'));
    }

    /** Product search for the "Add products" box. */
    public function variants(Request $request): JsonResponse
    {
        $this->authorizeArea('orders');
        $term = mb_strtolower(trim((string) $request->query('q')));

        // ?ids=3,7 is how a saved draft loads its products back, at today's prices.
        $ids = collect(explode(',', (string) $request->query('ids')))->filter(fn ($id) => ctype_digit(trim($id)))->take(100);

        $variants = ProductVariant::query()
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->where('is_active', true)
            ->whereHas('product', fn ($p) => $p->where('status', 'active'))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => Search::like($w, 'sku', $term)
                ->orWhereHas('product', fn ($p) => Search::likeAny($p, ['name->en', 'name->ar'], $term))))
            ->with(['product.media', 'optionValues', 'image'])
            ->orderBy('product_id')->orderBy('position')->limit($ids->isNotEmpty() ? 100 : 20)->get();

        return response()->json(['variants' => $variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'title' => $v->product->name,
            'variant' => $v->title() ?: null,
            'sku' => $v->sku,
            'price' => $v->price->minor,
            'price_label' => price_admin($v->price),
            'stock' => $v->track_inventory && ! $v->allow_backorder ? $v->stock_quantity : null,
            'image' => ($v->image ?? $v->product->media->first())?->url('thumb'),
        ])]);
    }

    public function customers(Request $request): JsonResponse
    {
        $this->authorizeArea('orders');
        $term = mb_strtolower(trim((string) $request->query('q')));
        $customers = User::query()->where('is_active', true)
            ->where(fn ($q) => Search::likeAny($q, ['name', 'email'], $term)->orWhere('phone', 'like', '%'.preg_replace('/\D/', '', $term).'%'))
            ->with(['addresses' => fn ($q) => $q->where('is_default', true)])->limit(8)->get();

        return response()->json(['customers' => $customers->map(fn (User $u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
            'address' => ($a = $u->addresses->first()) ? $a->only(['name', 'phone', 'country', 'state', 'city', 'address_line', 'building']) : null,
        ])]);
    }

    /** Delivery options for the chosen items and destination (country + state). */
    public function rates(Request $request, ShippingService $shipping): JsonResponse
    {
        $this->authorizeArea('orders');
        $request->replace(\App\Support\AddressInput::normalize($request->all()));
        $data = $request->validate(['lines' => ['array'], 'lines.*' => ['integer', 'min:1'], 'country' => ['required', 'string', 'size:2'], 'state' => ['nullable', 'string', 'max:100']]);
        $variants = ProductVariant::query()->whereKey(array_keys($data['lines'] ?? []))->get()->keyBy('id');
        $subtotal = Money::zero(config('store.base_currency'));
        $weight = 0;
        foreach ($data['lines'] ?? [] as $id => $qty) {
            if ($v = $variants->get($id)) {
                $subtotal = $subtotal->add($v->price->multiply($qty));
                $weight += (int) $v->weight_grams * $qty;
            }
        }

        return response()->json(['rates' => collect($shipping->rates($subtotal, \App\Support\Countries::shippingRegion($data['country'], $data['state'] ?? null), $weight))->map(fn ($r) => [
            'code' => $r->code, 'name' => $r->name[app()->getLocale()] ?? reset($r->name), 'price' => price_admin($r->price),
        ])->values()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeArea('orders');
        $request->merge(['phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone')), 'email' => mb_strtolower(trim((string) $request->input('email')))]);
        $request->replace(\App\Support\AddressInput::normalize($request->all()));
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:'.(int) config('store.draft_order_max_lines', 100)],
            'lines.*' => ['integer', 'min:1', 'max:999'],
            'customer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\+?\d{7,15}$/'],
            'name' => ['required', 'string', 'max:120'],
            ...\App\Support\AddressInput::rules(),
            'city' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
            'shipping_method' => ['required', 'string', 'max:60'],
            'discount_code' => ['nullable', 'string', 'max:40'],
            'payment' => ['required', Rule::in(['paid', 'cod'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'locale' => ['required', Rule::in(app(\App\Support\Localization::class)->codes())],
            'draft_id' => ['nullable', 'integer'],
        ], ['phone.regex' => __('store.checkout.phone_invalid')], ['lines' => __('admin.draft.products')]);

        $order = app(\App\Services\Orders\DraftOrderService::class)->place($data, $request->user('admin'));

        if ($request->boolean('notify')) {
            try {
                Mail::to($order->email)->send(new OrderPlaced($order->fresh()));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('admin.orders.show', $order)->with('success', __('admin.draft.created', ['number' => $order->number]));
    }
}
