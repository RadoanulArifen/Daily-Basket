<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Banner;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends AdminController
{
    public function index(): View
    {
        return view('admin.content.banners.index', ['banners' => Banner::query()->with('image')->orderBy('placement')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.content.banners.form', ['banner' => new Banner(['placement' => 'hero'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $banner = Banner::create($this->validated($request));

        return redirect()->route('admin.banners.edit', $banner)->with('success', __('admin.banners.created'));
    }

    public function edit(Banner $banner): View
    {
        return view('admin.content.banners.form', ['banner' => $banner->load('image')]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->validated($request));

        return back()->with('success', __('admin.banners.saved'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', __('admin.banners.deleted'));
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
            'image_id' => $request->filled('image_id') ? $request->input('image_id') : null,
            'sort_order' => $request->filled('sort_order') ? $request->input('sort_order') : 0,
        ]);

        $data = $request->validate([
            'placement' => ['required', Rule::in(Banner::placements())],
            'title.en' => ['required', 'string', 'max:120'],
            'title.ar' => ['nullable', 'string', 'max:120'],
            'text.*' => ['nullable', 'string', 'max:300'],
            'button_label.*' => ['nullable', 'string', 'max:40'],
            // A path on this site (/collections/sale) or a full https link.
            'url' => ['nullable', 'string', 'max:500', 'regex:#^(/[^\s]*|https?://[^\s]+)$#'],
            'image_id' => ['nullable', 'integer', Rule::exists(Media::class, 'id')],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ], ['url.regex' => __('admin.banners.url_format')], ['title.en' => __('admin.banners.fields.title').' (English)']);

        $tz = config('store.display_timezone');
        foreach (['starts_at', 'ends_at'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? \Illuminate\Support\Carbon::parse($data[$field], $tz)->utc() : null;
        }
        foreach (['title', 'text', 'button_label'] as $field) {
            $data[$field] = array_filter($data[$field] ?? []);
        }

        return $data;
    }
}
