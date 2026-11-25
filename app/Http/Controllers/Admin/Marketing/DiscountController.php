<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Discount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountController extends AdminController
{
    public function index(): View
    {
        return view('admin.marketing.discounts.index', ['discounts' => Discount::query()->latest('id')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.marketing.discounts.form', ['discount' => new Discount(['type' => 'percentage', 'code' => strtoupper(Str::random(8))])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $discount = Discount::create($this->validated($request));

        return redirect()->route('admin.discounts.edit', $discount)->with('success', __('admin.discounts.created'));
    }

    public function edit(Discount $discount): View
    {
        return view('admin.marketing.discounts.form', compact('discount'));
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $discount->update($this->validated($request, $discount));

        return back()->with('success', __('admin.discounts.saved'));
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return redirect()->route('admin.discounts.index')->with('success', __('admin.discounts.deleted'));
    }

    private function validated(Request $request, ?Discount $discount = null): array
    {
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'is_active' => $request->boolean('is_active'),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique(Discount::class)->ignore($discount)],
            'type' => ['required', Rule::in(Discount::TYPES)],
            'value' => ['exclude_if:type,free_shipping', 'required', 'numeric', 'gt:0', 'decimal:0,2', $request->input('type') === 'percentage' ? 'max:100' : 'max:9999999.99'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ], ['code.regex' => __('admin.discounts.code_format')], [
            'code' => __('admin.discounts.fields.code'),
            'value' => __('admin.discounts.fields.value'),
            'ends_at' => __('admin.discounts.fields.ends_at'),
        ]);

        $data['value'] = $data['value'] ?? '0.00';
        $tz = config('store.display_timezone');
        foreach (['starts_at', 'ends_at'] as $field) {
            // Dates are typed in UAE time.
            $data[$field] = filled($data[$field] ?? null) ? \Illuminate\Support\Carbon::parse($data[$field], $tz)->utc() : null;
        }

        return $data;
    }
}
