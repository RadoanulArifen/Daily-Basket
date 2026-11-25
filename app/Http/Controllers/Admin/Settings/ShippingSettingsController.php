<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\ShippingSettingsRequest;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\AramexCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShippingSettingsController extends AdminController
{
    public function edit(SettingsService $settings, AramexCarrier $aramex): View
    {
        return view('admin.settings.shipping', [
            'values' => $settings->group('shipping'),
            'aramexConfigured' => $aramex->isConfigured(),
        ]);
    }

    public function update(ShippingSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();
        $data['flat_name'] = array_filter($data['flat_name']);
        $data['flat_description'] = array_filter($data['flat_description'] ?? []);
        // None ticked = deliver to every country.
        $data['countries'] = array_values(array_unique($data['countries'] ?? []));
        $settings->setMany('shipping', $data + ['reviewed_at' => now()->toIso8601String()]);

        return back()->with('success', __('admin.settings.saved'));
    }
}
