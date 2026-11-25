<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\TaxSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxSettingsController extends AdminController
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.tax', [
            'values' => $settings->group('tax'),
        ]);
    }

    public function update(TaxSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();

        $settings->setMany('tax', [
            'enabled' => $data['enabled'],
            // Stored as a string so the rate is never touched by float rounding.
            'rate' => number_format((float) ($data['rate'] ?? 0), 2, '.', ''),
            'label' => $data['label'],
            'prices_include_tax' => $data['prices_include_tax'],
            'show_on_invoice' => $data['show_on_invoice'],
            'reviewed_at' => now()->toIso8601String(),
        ]);

        return back()->with('success', __('admin.settings.saved'));
    }
}
