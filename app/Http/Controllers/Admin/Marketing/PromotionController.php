<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends AdminController
{
    public function index(): View
    {
        return view('admin.marketing.promotions.index', ['promotions' => Promotion::query()->with('category')->latest('id')->get()]);
    }

    public function create(): View
    {
        return $this->form(new Promotion(['buy_quantity' => 2, 'get_quantity' => 1]));
    }

    public function store(Request $request): RedirectResponse
    {
        $promotion = Promotion::create($this->validated($request));

        return redirect()->route('admin.promotions.edit', $promotion)->with('success', __('admin.promotions.created'));
    }

    public function edit(Promotion $promotion): View
    {
        return $this->form($promotion);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request));

        return back()->with('success', __('admin.promotions.saved'));
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', __('admin.promotions.deleted'));
    }

    private function form(Promotion $promotion): View
    {
        return view('admin.marketing.promotions.form', [
            'promotion' => $promotion,
            'categories' => Category::flatTree(),
            'products' => Product::query()->where('status', '!=', 'archived')->orderBy('name->en')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        $data = $request->validate([
            'name.en' => ['nullable', 'string', 'max:80'],
            'name.ar' => ['nullable', 'string', 'max:80'],
            'buy_quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'get_quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'get_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'applies_to' => ['required', Rule::in(Promotion::SCOPES)],
            'category_id' => ['nullable', 'required_if:applies_to,category', 'integer', Rule::exists(Category::class, 'id')],
            'product_ids' => ['nullable', 'required_if:applies_to,products', 'array', 'max:200'],
            'product_ids.*' => ['integer', Rule::exists(Product::class, 'id')],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ], [], [
            'category_id' => __('admin.promotions.fields.category'),
            'product_ids' => __('admin.promotions.fields.products'),
            'ends_at' => __('admin.discounts.fields.ends_at'),
        ]);

        $tz = config('store.display_timezone');
        foreach (['starts_at', 'ends_at'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? Carbon::parse($data[$field], $tz)->utc() : null;
        }

        $data['name'] = array_filter($data['name'] ?? []);
        $data['category_id'] = $data['applies_to'] === 'category' ? $data['category_id'] : null;
        $data['product_ids'] = $data['applies_to'] === 'products' ? array_values(array_map('intval', $data['product_ids'] ?? [])) : null;

        return $data;
    }
}
