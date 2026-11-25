<?php

namespace App\Services\Dashboard;

use App\Models\Product;
use App\Services\Currency\CurrencyService;
use App\Services\Settings\SettingsService;

/** The dashboard's "Setup guide": each step's title, text, link, icon and whether it is done. */
class SetupStepsBuilder
{
    public function __construct(private SettingsService $settings, private CurrencyService $currencies)
    {
    }

    /** @return list<array{title: string, text: string, done: bool, url: string, icon: string}> */
    public function build(): array
    {
        $settings = $this->settings;

        return [
            [
                'title' => __('admin.dashboard.steps.details.title'),
                'text' => __('admin.dashboard.steps.details.text'),
                'done' => filled($settings->get('general.store_email')) && filled($settings->get('general.store_phone')),
                'url' => route('admin.settings.general.edit'),
                'icon' => 'store',
            ],
            [
                'title' => __('admin.dashboard.steps.tax.title'),
                'text' => __('admin.dashboard.steps.tax.text'),
                'done' => filled($settings->get('tax.reviewed_at')),
                'url' => route('admin.settings.tax.edit'),
                'icon' => 'receipt',
            ],
            [
                'title' => __('admin.dashboard.steps.currencies.title'),
                'text' => __('admin.dashboard.steps.currencies.text'),
                'done' => filled($settings->get('currencies.reviewed_at')),
                'url' => route('admin.settings.currencies.index'),
                'icon' => 'coins',
            ],
            [
                'title' => __('admin.dashboard.steps.products.title'),
                'text' => __('admin.dashboard.steps.products.text'),
                'done' => Product::query()->exists(),
                'url' => route('admin.products.create'),
                'icon' => 'package',
            ],
            ['title' => __('admin.dashboard.steps.payments.title'), 'text' => __('admin.dashboard.steps.payments.text'), 'done' => filled($settings->get('payments.reviewed_at')), 'url' => route('admin.settings.payments.edit'), 'icon' => 'credit-card'],
            ['title' => __('admin.dashboard.steps.shipping.title'), 'text' => __('admin.dashboard.steps.shipping.text'), 'done' => filled($settings->get('shipping.reviewed_at')), 'url' => route('admin.settings.shipping.edit'), 'icon' => 'truck'],
        ];
    }
}
