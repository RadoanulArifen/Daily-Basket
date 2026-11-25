<?php

// SECURITY: every route is behind auth:web; every address is loaded with
// CustomerAddress::where('user_id', auth('web')->id())->findOrFail($id) plus an abort_if ownership check.

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AddressController extends Controller
{
    /*
     | OWNERSHIP: edit, update, destroy and make-default all load the address through
     | ownedAddress(): $request->user('web')->addresses()->findOrFail($id) — another
     | customer's id gives 404 — plus abort_unless($address->user_id === …, 403).
     */
    private function ownedAddress(Request $request, int $id): CustomerAddress
    {
        // Scoped to the signed-in customer, so another customer's id simply does not exist
        // here — changing the number in the URL gives a 404, not someone else's address.
        $address = CustomerAddress::where('user_id', auth('web')->id())->findOrFail($id);   // only this customer's addresses

        abort_unless($address->user_id === $request->user('web')->id, 403);   // ownership: only the signed-in customer's own address

        return $address;
    }

    public function index(Request $request): View
    {
        return view('storefront.account.addresses.index', ['addresses' => $request->user('web')->addresses()->get()]);
    }

    public function create(): View
    {
        return view('storefront.account.addresses.form', ['address' => new CustomerAddress]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        if ($user->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            throw ValidationException::withMessages(['address_line' => __('store.addresses.limit', ['max' => CustomerAddress::MAX_PER_CUSTOMER])]);
        }

        $address = $user->addresses()->create($this->validated($request) + ['is_default' => false]);

        if ($request->boolean('is_default') || $user->addresses()->count() === 1) {
            $address->makeDefault();
        }

        return redirect(store_route('account.addresses'))->with('status', __('store.addresses.saved'));
    }

    public function edit(Request $request, int $address): View
    {
        $model = $this->ownedAddress($request, $address);
        abort_if($model->user_id !== auth('web')->id(), 403);   // ownership

        return view('storefront.account.addresses.form', ['address' => $model]);
    }

    public function update(Request $request, int $address): RedirectResponse
    {
        $model = $this->ownedAddress($request, $address);
        abort_if($model->user_id !== auth('web')->id(), 403);   // ownership
        $model->update($this->validated($request));

        if ($request->boolean('is_default')) {
            $model->makeDefault();
        }

        return redirect(store_route('account.addresses'))->with('status', __('store.addresses.saved'));
    }

    public function destroy(Request $request, int $address): RedirectResponse
    {
        $model = $this->ownedAddress($request, $address);
        abort_if($model->user_id !== auth('web')->id(), 403);   // ownership
        $wasDefault = $model->is_default;
        $model->delete();

        // Another address takes over as default.
        if ($wasDefault && ($next = $request->user('web')->addresses()->first())) {
            $next->makeDefault();
        }

        return redirect(store_route('account.addresses'))->with('status', __('store.addresses.deleted'));
    }

    public function makeDefault(Request $request, int $address): RedirectResponse
    {
        $model = $this->ownedAddress($request, $address);
        abort_if($model->user_id !== auth('web')->id(), 403);   // ownership
        $model->makeDefault();

        return redirect(store_route('account.addresses'))->with('status', __('store.addresses.default_set'));
    }

    private function validated(Request $request): array
    {
        $request->merge(['phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone'))]);
        $request->replace(\App\Support\AddressInput::normalize($request->all()));

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?\d{7,15}$/'],
            ...\App\Support\AddressInput::rules(),
            'city' => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
        ], ['phone.regex' => __('store.checkout.phone_invalid')], [
            'name' => __('store.checkout.full_name'),
            'phone' => __('store.auth.phone'),
            'country' => __('store.checkout.country'),
            'state' => __('store.checkout.state'),
            'city' => __('store.checkout.city'),
            'address_line' => __('store.checkout.address_line'),
        ]);

        // UAE addresses also keep their emirate code (Aramex).
        return $data + ['emirate' => \App\Support\Countries::emirate($data['country'], $data['state'] ?? null)];
    }
}
