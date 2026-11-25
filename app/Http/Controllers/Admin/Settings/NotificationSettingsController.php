<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Notifications\EmailTemplates;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends AdminController
{
    public function edit(SettingsService $settings, EmailTemplates $templates): View
    {
        return view('admin.settings.notifications', [
            'values' => $settings->group('emails'),
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        $rules = [];
        foreach (EmailTemplates::ALL_EVENTS as $event) {
            $rules["{$event}_subject.*"] = ['nullable', 'string', 'max:150'];
            $rules["{$event}_intro.*"] = ['nullable', 'string', 'max:1000'];
            if (in_array($event, EmailTemplates::EVENTS, true) && $event !== 'placed') {
                $rules["{$event}_enabled"] = ['boolean'];
            }
        }
        $rules['cart_reminder_subject.*'] = ['nullable', 'string', 'max:150'];
        $rules['cart_reminder_intro.*'] = ['nullable', 'string', 'max:1000'];
        // The two later steps of the sequence; 0 means "skip this one".
        $rules['cart_reminder_hours_2'] = ['nullable', 'integer', 'min:0', 'max:336'];
        $rules['cart_reminder_hours_3'] = ['nullable', 'integer', 'min:0', 'max:336'];
        $rules['cart_reminder_hours'] = ['nullable', 'integer', 'min:1', 'max:72'];
        $data = $request->validate($rules);

        $values = [];
        foreach (EmailTemplates::ALL_EVENTS as $event) {
            $values["{$event}_subject"] = array_filter($data["{$event}_subject"] ?? []);
            $values["{$event}_intro"] = array_filter($data["{$event}_intro"] ?? []);
            if (in_array($event, EmailTemplates::EVENTS, true) && $event !== 'placed') {
                $values["{$event}_enabled"] = $request->boolean("{$event}_enabled");
            }
        }
        $values['cart_reminder_enabled'] = $request->boolean('cart_reminder_enabled');
        $values['cart_reminder_hours'] = (int) ($data['cart_reminder_hours'] ?? 3);
        $values['cart_reminder_hours_2'] = (int) ($data['cart_reminder_hours_2'] ?? 24);
        $values['cart_reminder_hours_3'] = (int) ($data['cart_reminder_hours_3'] ?? 72);
        $values['cart_reminder_subject'] = array_filter($data['cart_reminder_subject'] ?? []);
        $values['cart_reminder_intro'] = array_filter($data['cart_reminder_intro'] ?? []);
        $settings->setMany('emails', $values);

        return back()->with('success', __('admin.settings.saved'));
    }
}
