<?php

// SECURITY: public enquiry form by design (throttled, reCAPTCHA, CSRF). There is no show/update/list
// action on the storefront; staff read requests in the admin (auth:admin, area: orders).

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\CustomOrderRequestReceived;
use App\Models\CustomOrderRequest;
use App\Models\Product;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The public "request a custom order" enquiry form. Guests may send it by design; it is
 * rate-limited (throttle:forms), protected by reCAPTCHA (App\Rules\Recaptcha on 'g-recaptcha-response')
 * and CSRF, and nothing is shown back — there is no show/edit/list action
 * for requests on the storefront (staff read them in Admin → Orders → Enquiries, which needs
 * auth:admin and the "orders" role area).
 */
class CustomOrderController extends Controller
{
    public function show(Request $request, StorefrontCatalog $catalog): View
    {
        // The product page links here with ?product=slug, so the form knows the piece.
        $product = filled($request->query('product'))
            ? $catalog->products()->where('slug', $request->query('product'))->with('brandRecord')->first()
            : null;

        return view('storefront.custom-order.show', [
            'product' => $product,
            'categories' => \App\Models\Category::query()->active()->whereNull('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'g-recaptcha-response' => [new \App\Rules\Recaptcha],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'product' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'dimensions' => ['nullable', 'string', 'max:160'],
            'material' => ['nullable', 'string', 'max:120'],
            'finish' => ['nullable', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'between:1,999'],
            'budget' => ['nullable', 'string', 'max:60'],
            'timeline' => ['nullable', 'string', 'max:60'],
            // The enquiry popup asks for size and material instead of a written message,
            // so one of the three has to be there.
            'message' => ['required_without_all:dimensions,material', 'nullable', 'string', 'min:10', 'max:3000'],
        ], [], [
            'name' => __('store.checkout.full_name'),
            'email' => __('store.auth.email'),
            'message' => __('store.custom.message'),
        ]);

        // The enquiry popup asks for size and material rather than a written message;
        // record those as the message so the enquiry reads properly in the admin.
        $data['message'] = $data['message'] ?? collect([
            __('store.custom.dimensions').': '.($data['dimensions'] ?? null),
            __('store.custom.material').': '.($data['material'] ?? null),
        ])->filter(fn ($line) => ! str_ends_with($line, ': '))->implode(' · ');

        $custom = CustomOrderRequest::query()->create([
            'user_id' => auth('web')->id(),
            'product_id' => Product::query()->where('slug', $data['product'] ?? '')->value('id'),
            'quantity' => $data['quantity'] ?? 1,
            'locale' => app()->getLocale(),
        ] + collect($data)->except(['product', 'quantity'])->all());

        if ($to = settings()->get('general.store_email')) {
            Mail::to($to)->send(new CustomOrderRequestReceived($custom));
        }

        // Tell the customer we have it, with what they sent.
        Mail::to($custom->email)->send(new \App\Mail\CustomerMessage(
            event: 'enquiry',
            vars: ['{name}' => \Illuminate\Support\Str::before($custom->name, ' ')],
            details: array_filter([
                __('store.custom.category') => $custom->category,
                __('store.custom.dimensions') => $custom->dimensions,
                __('store.custom.material') => $custom->material,
                __('store.contact.message') => $custom->message,
            ]),
            lang: $custom->locale,
        ));

        if (request()->expectsJson()) {
            return response()->json(['sent' => true, 'message' => __('store.custom.sent_text')]);
        }

        return redirect()->to(store_route('custom-order').'?sent=1')->with('custom_sent', true);
    }
}
