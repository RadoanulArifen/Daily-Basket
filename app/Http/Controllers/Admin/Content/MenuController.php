<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Navigation\MenuRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends AdminController
{
    public function index(): View
    {
        return view('admin.content.menus.index', ['menus' => Menu::query()->withCount('items')->orderBy('id')->get()]);
    }

    public function edit(Menu $menu): View
    {
        $items = $menu->items()->orderBy('sort_order')->get();

        return view('admin.content.menus.edit', [
            'menu' => $menu,
            'topLevel' => $items->whereNull('parent_id')->values(),
            'children' => $items->whereNotNull('parent_id')->groupBy('parent_id'),
        ]);
    }

    public function storeItem(Request $request, Menu $menu, MenuRepository $menus): RedirectResponse
    {
        $menu->items()->create($this->validated($request, $menu));
        $menus->flush();

        return back()->with('success', __('admin.menus.item_added'));
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item, MenuRepository $menus): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->update($this->validated($request, $menu, $item));
        $menus->flush();

        return back()->with('success', __('admin.menus.item_saved'));
    }

    public function destroyItem(Menu $menu, MenuItem $item, MenuRepository $menus): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->delete(); // its sub-items go too (database cascade)
        $menus->flush();

        return back()->with('success', __('admin.menus.item_deleted'));
    }

    private function validated(Request $request, Menu $menu, ?MenuItem $item = null): array
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
            'open_in_new_tab' => $request->boolean('open_in_new_tab'),
            'parent_id' => $request->filled('parent_id') ? $request->input('parent_id') : null,
            'sort_order' => $request->filled('sort_order') ? $request->input('sort_order') : 0,
        ]);

        $data = $request->validate([
            'label.en' => ['required', 'string', 'max:60'],
            'label.ar' => ['nullable', 'string', 'max:60'],
            'url' => ['required', 'string', 'max:500', 'regex:#^(/[^\s]*|https?://[^\s]+|mailto:[^\s]+|tel:[^\s]+)$#'],
            // One level of sub-items, and only under this menu's top-level items.
            'parent_id' => ['nullable', 'integer', Rule::exists('menu_items', 'id')->where('menu_id', $menu->id)->whereNull('parent_id'),
                $item ? Rule::notIn([$item->id]) : 'integer'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'open_in_new_tab' => ['boolean'],
        ], ['url.regex' => __('admin.menus.url_format')], ['label.en' => __('admin.menus.fields.label').' (English)', 'url' => __('admin.menus.fields.url')]);

        // An item that has sub-items must stay top-level.
        if ($item && $data['parent_id'] && $item->children()->exists()) {
            $data['parent_id'] = null;
        }

        $data['label'] = array_filter($data['label']);

        return $data;
    }
}
