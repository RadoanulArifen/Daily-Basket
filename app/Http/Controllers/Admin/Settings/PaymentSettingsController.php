<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\PaymentSettingsRequest;
use App\Services\Payments\StripeGateway;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentSettingsController extends AdminController
{
    public function edit(SettingsService $settings, StripeGateway $stripe): View
    {
        return view('admin.settings.payments', [
            'values' => $settings->group('payments'),
            'stripeConfigured' => $stripe->isConfigured(),
            'stripeTestMode' => $stripe->isTestMode(),
            'webhookConfigured' => filled(config('services.stripe.webhook_secret')),
            'webhookUrl' => route('webhooks.stripe'),
        ]);
    }

    public function update(PaymentSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $settings->setMany('payments', $request->validated() + ['reviewed_at' => now()->toIso8601String()]);

        return back()->with('success', __('admin.settings.saved'));
    }
}
