<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\CheckoutSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutSettingsController extends AdminController
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.checkout', [
            'values' => $settings->group('checkout'),
        ]);
    }

    public function update(CheckoutSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $settings->setMany('checkout', $request->validated());

        return back()->with('success', __('admin.settings.saved'));
    }
}
