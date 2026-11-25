<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsRequest extends FormRequest
{
    /** Fields with an English and an Arabic value. */
    public const TRANSLATABLE = ['store_name', 'tagline', 'address', 'opening_hours', 'announcement_text', 'meta_description'];

    public const PLAIN = ['store_email', 'store_phone', 'whatsapp', 'trn', 'instagram_url', 'facebook_url', 'tiktok_url', 'x_url', 'youtube_url', 'linkedin_url'];

    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'announcement_enabled' => $this->boolean('announcement_enabled'),
            'remove_logo' => $this->boolean('remove_logo'),
            'remove_logo_white' => $this->boolean('remove_logo_white'),
        ]);
    }

    public function rules(): array
    {
        $maxKb = config('store.uploads.max_image_kb');

        return [
            'store_name' => ['required', 'array'],
            'store_name.en' => ['required', 'string', 'max:100'],
            'store_name.ar' => ['nullable', 'string', 'max:100'],
            'tagline.*' => ['nullable', 'string', 'max:160'],
            'address.*' => ['nullable', 'string', 'max:500'],
            'announcement_text.*' => ['nullable', 'string', 'max:160'],
            'meta_description.*' => ['nullable', 'string', 'max:160'],

            'store_email' => ['required', 'email', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+()-]+$/'],
            'opening_hours.*' => ['nullable', 'string', 'max:200'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+()-]+$/'],
            'trn' => ['nullable', 'string', 'regex:/^\d{15}$/'],

            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https', 'max:255'],
            'x_url' => ['nullable', 'url:https', 'max:255'],

            'announcement_enabled' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', "max:{$maxKb}", 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo' => ['boolean'],
            'logo_white' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', "max:{$maxKb}", 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo_white' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'store_name.en' => __('admin.settings.general.store_name').' (English)',
            'store_name.ar' => __('admin.settings.general.store_name').' (العربية)',
            'store_email' => __('admin.settings.general.store_email'),
            'store_phone' => __('admin.settings.general.store_phone'),
            'trn' => __('admin.settings.general.trn'),
            'logo' => __('admin.settings.general.logo_dark'),
            'logo_white' => __('admin.settings.general.logo_white'),
        ];
    }

    public function messages(): array
    {
        return [
            'trn.regex' => __('admin.settings.general.trn_invalid'),
        ];
    }
}
