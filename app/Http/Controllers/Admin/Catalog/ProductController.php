<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Actions\Catalog\SaveProduct;
use App\Enums\ProductStatus;
use App\Http\Requests\Admin\Catalog\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends AdminController
{
    public const SORTS = ['newest', 'oldest', 'updated', 'name', 'name_desc'];

    public function index(\App\Http\Requests\Admin\Lists\ProductListRequest $request): View
    {
        $filters = $request->validated();
        $products = app(\App\Services\Catalog\ProductFilter::class)
            ->apply(Product::query()->with(['media' => fn ($q) => $q->select('media.*'), 'variants', 'categories']), $filters)
            ->paginate((int) config('store.pagination.admin_list', 50))
            ->withQueryString();

        return view('admin.catalog.products.index', [
            'products' => $products,
            'filters' => $filters,
            // A count per status in one query: SELECT status, COUNT(*) … GROUP BY status (no user input).
            'statusCounts' => Product::query()->toBase()->select('status')->selectRaw('count(*) as aggregate')
                ->groupBy('status')->pluck('aggregate', 'status')->map(fn ($n) => (int) $n)->filter(),
            'categories' => Category::flatTree(),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.form', [
            'product' => new Product(['status' => ProductStatus::Draft]),
            'formState' => app(\App\Services\Catalog\ProductFormState::class)->build(null),
            'categories' => Category::flatTree(),
        ]);
    }

    public function store(ProductRequest $request, SaveProduct $save): RedirectResponse
    {
        $this->authorizeArea('catalog');
        $product = $save->handle($request->validated(), null, $request->user('admin'));

        return redirect()->route('admin.products.edit', $product)->with('success', __('admin.products.created'));
    }

    public function edit(Product $product): View
    {
        return view('admin.catalog.products.form', [
            'product' => $product,
            'formState' => app(\App\Services\Catalog\ProductFormState::class)->build($product),
            'categories' => Category::flatTree(),
        ]);
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $save): RedirectResponse
    {
        $this->authorizeArea('catalog');
        $save->handle($request->validated(), $product, $request->user('admin'));

        return redirect()->route('admin.products.edit', $product)->with('success', __('admin.products.saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeArea('catalog');
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('admin.products.deleted'));
    }

    public function bulk(Request $request): RedirectResponse
    {
        $this->authorizeArea('catalog');
        $data = $request->validate([
            'action' => ['required', Rule::in(['active', 'draft', 'archived', 'delete'])],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $query = Product::query()->whereKey($data['ids']);

        if ($data['action'] === 'delete') {
            $count = $query->get()->each->delete()->count();
        } else {
            $count = 0;
            foreach ($query->get() as $product) {
                $product->status = ProductStatus::from($data['action']);
                $product->published_at ??= $data['action'] === 'active' ? now() : null;
                $product->save();
                $count++;
            }
        }

        return back()->with('success', trans_choice('admin.products.bulk_done', $count, ['count' => $count]));
    }

}
