<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\GeneralSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GeneralSettingsController extends AdminController
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.general', [
            'values' => $settings->group('general'),
        ]);
    }

    public function update(GeneralSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $values = [];

        foreach (GeneralSettingsRequest::TRANSLATABLE as $field) {
            $values[$field] = array_map(
                fn ($v) => filled($v) ? trim($v) : null,
                array_intersect_key((array) $request->input($field, []), config('store.all_locales', config('store.locales'))),
            );
        }

        foreach (GeneralSettingsRequest::PLAIN as $field) {
            $values[$field] = $request->filled($field) ? trim($request->input($field)) : null;
        }

        $values['announcement_enabled'] = $request->boolean('announcement_enabled');

        // Which languages the store uses. The default one is always kept.
        $chosen = array_values(array_unique([...(array) $request->input('locales', []), config('store.default_locale')]));
        $values['locales'] = array_values(array_intersect(array_keys(config('store.all_locales', config('store.locales'))), $chosen));

        $disk = Storage::disk(config('store.uploads.disk'));
        $currentLogos = ['logo' => $settings->get('general.logo'), 'logo_white' => $settings->get('general.logo_white')];

        // Two logos: the dark one for light backgrounds, the white one for dark ones.
        foreach (['logo', 'logo_white'] as $field) {
            if ($request->hasFile($field)) {
                $values[$field] = $request->file($field)->store('settings', config('store.uploads.disk'));
            } elseif ($request->boolean("remove_{$field}")) {
                $values[$field] = null;
            }
        }

        $settings->setMany('general', $values);

        // The replaced file is not needed any more.
        foreach ($currentLogos as $field => $was) {
            if (array_key_exists($field, $values) && $was && $was !== $values[$field]) {
                $disk->delete($was);
            }
        }

        return back()->with('success', __('admin.settings.saved'));
    }
}
