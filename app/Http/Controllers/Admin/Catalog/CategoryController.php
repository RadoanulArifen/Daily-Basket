<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Catalog\CategoryRequest;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends AdminController
{
    public function index(): View
    {
        return view('admin.catalog.categories.index', [
            'categories' => Category::flatTree(Category::query()->with('image')->withCount('products')),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.categories.form', [
            'category' => new Category,
            'parents' => Category::flatTree(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category;
        $this->fill($category, $request->validated());

        return redirect()->route('admin.categories.edit', $category)->with('success', __('admin.categories.created'));
    }

    public function edit(Category $category): View
    {
        $category->load('image');
        $excluded = $category->descendantIds();

        return view('admin.catalog.categories.form', [
            'category' => $category,
            'parents' => Category::flatTree()->reject(fn (Category $c) => in_array($c->id, $excluded, true))->values(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->fill($category, $request->validated());

        return redirect()->route('admin.categories.edit', $category)->with('success', __('admin.categories.saved'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        app(\App\Services\Catalog\CatalogDeletion::class)->deleteCategory($category);   // locked, scoped, counted, in one transaction

        return redirect()->route('admin.categories.index')->with('success', __('admin.categories.deleted'));
    }

    private function fill(Category $category, array $data): void
    {
        $category->fill([
            'parent_id' => $data['parent_id'],
            'name' => array_filter($data['name']),
            'description' => array_filter($data['description'] ?? []),
            'seo_title' => array_filter($data['seo_title'] ?? []),
            'seo_description' => array_filter($data['seo_description'] ?? []),
            'image_id' => $data['image_id'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);

        $category->slug = $data['slug']
            ?: ($category->exists ? $category->slug : Slug::unique(Category::class, $data['name']['en'], null, 'category'));

        $category->save();
    }
}
