<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingSettingsController extends AdminController
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.tracking', ['values' => $settings->group('tracking')]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        $request->merge(['ga_measurement_id' => $request->filled('ga_measurement_id') ? strtoupper(trim($request->input('ga_measurement_id'))) : null]);

        $data = $request->validate([
            'ga_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/'],
        ], ['ga_measurement_id.regex' => __('admin.settings.tracking.ga_invalid')]);

        $settings->setMany('tracking', $data);

        return back()->with('success', __('admin.settings.saved'));
    }
}
