<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Page;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends AdminController
{
    public function index(): View
    {
        return view('admin.content.pages.index', ['pages' => Page::query()->orderBy('slug')->get()]);
    }

    public function create(): View
    {
        return view('admin.content.pages.form', ['page' => new Page]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = new Page;
        $this->save($request, $page);

        return redirect()->route('admin.pages.edit', $page)->with('success', __('admin.pages.created'));
    }

    public function edit(Page $page): View
    {
        return view('admin.content.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->save($request, $page);

        return back()->with('success', __('admin.pages.saved'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', __('admin.pages.deleted'));
    }

    private function save(Request $request, Page $page): void
    {
        $request->merge([
            'slug' => $request->filled('slug') ? strtolower(trim($request->input('slug'))) : null,
            'is_active' => $request->boolean('is_active'),
            'show_contact_form' => $request->boolean('show_contact_form'),
        ]);

        $data = $request->validate([
            'title.en' => ['required', 'string', 'max:150'],
            'title.ar' => ['nullable', 'string', 'max:150'],
            'eyebrow.*' => ['nullable', 'string', 'max:80'],
            'heading.*' => ['nullable', 'string', 'max:200'],
            'intro.*' => ['nullable', 'string', 'max:1000'],
            'body.*' => ['nullable', 'string', 'max:100000'],
            'footnote.*' => ['nullable', 'string', 'max:500'],
            'image_ids' => ['nullable', 'array', 'max:3'],
            'image_ids.*' => ['integer', Rule::exists(\App\Models\Media::class, 'id')],
            'seo_title.*' => ['nullable', 'string', 'max:70'],
            'seo_description.*' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique(Page::class)->ignore($page)],
            'is_active' => ['boolean'],
            'show_contact_form' => ['boolean'],
        ], ['slug.regex' => __('admin.common.url_handle_invalid')], ['title.en' => __('admin.pages.fields.title').' (English)']);

        $page->fill([
            'title' => array_filter($data['title']),
            'eyebrow' => array_filter($data['eyebrow'] ?? []),
            'heading' => array_filter($data['heading'] ?? []),
            'intro' => array_filter($data['intro'] ?? []),
            'body' => array_filter($data['body'] ?? []),
            'footnote' => array_filter($data['footnote'] ?? []),
            'image_ids' => array_values(array_map('intval', $data['image_ids'] ?? [])) ?: null,
            'seo_title' => array_filter($data['seo_title'] ?? []),
            'seo_description' => array_filter($data['seo_description'] ?? []),
            'is_active' => $data['is_active'],
            'show_contact_form' => $data['show_contact_form'],
        ]);
        $page->slug = $data['slug'] ?: ($page->exists ? $page->slug : Slug::unique(Page::class, $data['title']['en'], null, 'page'));
        $page->save();
    }
}
