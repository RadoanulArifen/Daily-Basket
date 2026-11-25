@extends('admin.settings.layout')

@section('title', __('admin.settings.general.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.general.title')" :back="route('admin.settings.index')" />

    <form method="POST" action="{{ route('admin.settings.general.update') }}" enctype="multipart/form-data"
          x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section :title="__('admin.settings.general.details_title')" :description="__('admin.settings.general.details_text')">
            <x-admin.translatable-input name="store_name" :label="__('admin.settings.general.store_name')" :values="$values['store_name'] ?? []" required maxlength="100" />
            <x-admin.translatable-input name="tagline" :label="__('admin.settings.general.tagline')" :values="$values['tagline'] ?? []" maxlength="160" :help="__('admin.settings.general.tagline_help')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="store_email" type="email" :label="__('admin.settings.general.store_email')" :value="$values['store_email'] ?? null" required :help="__('admin.settings.general.store_email_help')" />
                <x-admin.input name="store_phone" type="tel" :label="__('admin.settings.general.store_phone')" :value="$values['store_phone'] ?? null" placeholder="+971 4 000 0000" dir="ltr" />
                <x-admin.input name="whatsapp" type="tel" :label="__('admin.settings.general.whatsapp')" :value="$values['whatsapp'] ?? null" placeholder="+971 50 000 0000" dir="ltr" />
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-admin.translatable-input name="opening_hours" :label="__('admin.settings.general.opening_hours')" :values="$values['opening_hours'] ?? []" :help="__('admin.settings.general.opening_hours_help')" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.business_title')" :description="__('admin.settings.general.business_text')">
            <x-admin.input name="trn" :label="__('admin.settings.general.trn')" :value="$values['trn'] ?? null" inputmode="numeric" maxlength="15" placeholder="100000000000003" dir="ltr" :help="__('admin.settings.general.trn_help')" />
            <x-admin.translatable-input name="address" :label="__('admin.settings.general.address')" :values="$values['address'] ?? []" multiline :rows="3" maxlength="500" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.languages')" :description="__('admin.settings.general.languages_help')">
            @php
                $localization = app(\App\Support\Localization::class);
                $enabledLocales = (array) ($values['locales'] ?? array_keys($localization->locales()));
            @endphp
            <div class="space-y-3">
                @foreach ($localization->supported() as $code => $meta)
                    @php $isDefault = $code === $localization->default(); @endphp
                    <label class="flex items-start gap-3 text-[14px]">
                        <input type="checkbox" name="locales[]" value="{{ $code }}"
                               @checked($isDefault || in_array($code, $enabledLocales, true))
                               @disabled($isDefault)
                               class="mt-0.5 size-4 accent-brand-600">
                        <span>
                            <span class="block font-medium">{{ $meta['name'] }} <span class="text-muted">({{ $meta['native'] }})</span></span>
                            <span class="block text-[13px] text-muted">{{ $isDefault ? __('admin.settings.general.language_default') : __('admin.settings.general.language_optional') }}</span>
                        </span>
                    </label>
                    {{-- The default language cannot be switched off, so it is posted anyway. --}}
                    @if ($isDefault)<input type="hidden" name="locales[]" value="{{ $code }}">@endif
                @endforeach
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.logo')" :description="__('admin.settings.general.logo_text')">
            <div class="grid gap-6 sm:grid-cols-2">
                @include('admin.settings.partials.logo-field', [
                    'field' => 'logo',
                    'label' => __('admin.settings.general.logo_dark'),
                    'help' => __('admin.settings.general.logo_dark_help'),
                ])
                @include('admin.settings.partials.logo-field', [
                    'field' => 'logo_white',
                    'label' => __('admin.settings.general.logo_white'),
                    'help' => __('admin.settings.general.logo_white_help'),
                ])
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.announcement_title')" :description="__('admin.settings.general.announcement_text')">
            <x-admin.toggle name="announcement_enabled" :label="__('admin.settings.general.announcement_show')" :checked="$values['announcement_enabled'] ?? false" />
            <x-admin.translatable-input name="announcement_text" :label="__('admin.settings.general.announcement_message')" :values="$values['announcement_text'] ?? []" maxlength="160" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.social_title')" :description="__('admin.settings.general.social_text')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="instagram_url" type="url" label="Instagram" :value="$values['instagram_url'] ?? null" placeholder="https://instagram.com/yourstore" dir="ltr" />
                <x-admin.input name="facebook_url" type="url" label="Facebook" :value="$values['facebook_url'] ?? null" placeholder="https://facebook.com/yourstore" dir="ltr" />
                <x-admin.input name="tiktok_url" type="url" label="TikTok" :value="$values['tiktok_url'] ?? null" placeholder="https://tiktok.com/@yourstore" dir="ltr" />
                <x-admin.input name="youtube_url" type="url" label="YouTube" :value="$values['youtube_url'] ?? null" placeholder="https://youtube.com/@yourstore" dir="ltr" />
                <x-admin.input name="linkedin_url" type="url" label="LinkedIn" :value="$values['linkedin_url'] ?? null" placeholder="https://linkedin.com/company/yourstore" dir="ltr" />
                <x-admin.input name="x_url" type="url" label="X" :value="$values['x_url'] ?? null" placeholder="https://x.com/yourstore" dir="ltr" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.general.seo_title')" :description="__('admin.settings.general.seo_text')">
            <x-admin.translatable-input name="meta_description" :label="__('admin.settings.general.meta_description')" :values="$values['meta_description'] ?? []" multiline :rows="3" maxlength="160" :help="__('admin.settings.general.meta_description_help')" />
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>
@endsection
