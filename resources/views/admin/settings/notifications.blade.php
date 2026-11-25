@extends('admin.settings.layout')

@section('title', __('admin.settings.email.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.email.title')" :back="route('admin.settings.index')" :subtitle="__('admin.settings.email.subtitle')" />

    <form method="POST" action="{{ route('admin.settings.notifications.update') }}" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.alert>
            {{ __('admin.settings.email.placeholders') }}
            <span dir="ltr" class="font-mono text-[13px]">{{ implode('  ', \App\Services\Notifications\EmailTemplates::PLACEHOLDERS) }}</span>
        </x-admin.alert>

        @foreach (\App\Services\Notifications\EmailTemplates::EVENTS as $event)
            <x-admin.form-section :title="__('admin.settings.email.events.'.$event)" :description="__('admin.settings.email.events_help.'.$event)">
                @if ($event !== 'placed')
                    <x-admin.toggle :name="$event.'_enabled'" :label="__('admin.settings.email.send')" :checked="$values[$event.'_enabled'] ?? true" />
                @endif
                <x-admin.translatable-input :name="$event.'_subject'" :label="__('admin.settings.email.subject')" :values="(array) ($values[$event.'_subject'] ?? [])" maxlength="150"
                    :help="__('admin.settings.email.default', ['text' => $templates->defaultText($event, 'subject', 'en')])" />
                <x-admin.translatable-input :name="$event.'_intro'" :label="__('admin.settings.email.intro')" :values="(array) ($values[$event.'_intro'] ?? [])" multiline :rows="3" maxlength="1000"
                    :help="__('admin.settings.email.default', ['text' => $templates->defaultText($event, 'intro', 'en')])" />
                @include('admin.settings.partials.email-preview', ['event' => $event])
            </x-admin.form-section>
        @endforeach

        {{-- The store's other customer emails. --}}
        @foreach (\App\Services\Notifications\EmailTemplates::CUSTOMER_EVENTS as $event)
            <x-admin.form-section :title="__('admin.settings.email.events.'.$event)" :description="__('admin.settings.email.events_help.'.$event)">
                <x-admin.translatable-input :name="$event.'_subject'" :label="__('admin.settings.email.subject')" :values="(array) ($values[$event.'_subject'] ?? [])" maxlength="150"
                    :help="__('admin.settings.email.default', ['text' => $templates->defaultText($event, 'subject', 'en')])" />
                <x-admin.translatable-input :name="$event.'_intro'" :label="__('admin.settings.email.intro')" :values="(array) ($values[$event.'_intro'] ?? [])" multiline :rows="3" maxlength="1000"
                    :help="__('admin.settings.email.default', ['text' => $templates->defaultText($event, 'intro', 'en')])" />
                @include('admin.settings.partials.email-preview', ['event' => $event])
            </x-admin.form-section>
        @endforeach

        <x-admin.form-section :title="__('admin.settings.email.reminder_title')" :description="__('admin.settings.email.reminder_text')">
            <x-admin.toggle name="cart_reminder_enabled" :label="__('admin.settings.email.send')" :checked="$values['cart_reminder_enabled'] ?? true" />
            <div class="max-w-xs">
                <x-admin.input name="cart_reminder_hours" type="number" min="1" max="72" :label="__('admin.settings.email.reminder_hours')" :value="$values['cart_reminder_hours'] ?? 3" :suffix="__('admin.settings.email.hours')" />
                {{-- Two later steps. Set either to 0 to skip it. --}}
                <x-admin.input name="cart_reminder_hours_2" type="number" min="0" max="336" :label="__('admin.settings.email.reminder_hours_2')" :value="old('cart_reminder_hours_2', settings()->get('emails.cart_reminder_hours_2', 24))" />
                <x-admin.input name="cart_reminder_hours_3" type="number" min="0" max="336" :label="__('admin.settings.email.reminder_hours_3')" :value="old('cart_reminder_hours_3', settings()->get('emails.cart_reminder_hours_3', 72))" />
            </div>
            <x-admin.translatable-input name="cart_reminder_subject" :label="__('admin.settings.email.subject')" :values="(array) ($values['cart_reminder_subject'] ?? [])" maxlength="150"
                :help="__('admin.settings.email.default', ['text' => $templates->defaultText('cart_reminder', 'subject', 'en')])" />
            <x-admin.translatable-input name="cart_reminder_intro" :label="__('admin.settings.email.intro')" :values="(array) ($values['cart_reminder_intro'] ?? [])" multiline :rows="3" maxlength="1000"
                :help="__('admin.settings.email.default', ['text' => $templates->defaultText('cart_reminder', 'intro', 'en')]).' '.__('admin.settings.email.reminder_placeholders')" />
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>

    @foreach (\App\Services\Notifications\EmailTemplates::ALL_EVENTS as $event)
        <form method="POST" action="{{ route('admin.settings.emails.send', $event) }}" id="email-send-{{ $event }}">@csrf</form>
    @endforeach
@endsection
