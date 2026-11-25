<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Brand;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Brands: the makers behind the pieces, shown on product and brand pages. */
class BrandController extends AdminController
{
    use \App\Http\Controllers\Concerns\HandlesFileUploads;

    public function index(): View
    {
        return view('admin.catalog.brands.index', [
            'brands' => Brand::query()->withCount('products')->orderBy('position')->orderBy('id')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.brands.form', ['brand' => new Brand]);
    }

    public function store(\App\Http\Requests\Admin\Catalog\BrandRequest $request): RedirectResponse
    {
        $brand = new Brand;
        $this->fill($brand, $request->validated());

        return redirect()->route('admin.brands.edit', $brand)->with('success', __('admin.brands.created'));
    }

    public function edit(Brand $brand): View
    {
        return view('admin.catalog.brands.form', ['brand' => $brand]);
    }

    public function update(\App\Http\Requests\Admin\Catalog\BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->fill($brand, $request->validated());

        return redirect()->route('admin.brands.edit', $brand)->with('success', __('admin.brands.saved'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        // Products keep working; they simply no longer name a brand. Scoped to this brand's own
        // products (WHERE brand_id = this id) and done in one transaction with the delete.
        app(\App\Services\Catalog\CatalogDeletion::class)->deleteBrand($brand);   // locked, scoped, counted, in one transaction

        return redirect()->route('admin.brands.index')->with('success', __('admin.brands.deleted'));
    }


    private function fill(Brand $brand, array $data): void
    {
        $brand->fill([
            'name' => array_filter($data['name']),
            'origin' => array_filter($data['origin'] ?? []),
            'description' => array_filter($data['description'] ?? []),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'position' => (int) ($data['position'] ?? 0),
        ]);

        $brand->slug = ($data['slug'] ?? null)
            ?: ($brand->exists ? $brand->slug : Slug::unique(Brand::class, $data['name']['en'], null, 'brand'));

        $this->handleFileUploads($brand, $data, ['logo', 'logo_small'], 'brands');

        $brand->save();
    }

}
