@extends('admin.settings.layout')

@section('title', __('admin.settings.payments.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.payments.title')" :back="route('admin.settings.index')" />

    <form method="POST" action="{{ route('admin.settings.payments.update') }}" x-data="{ dirty: false }" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section data-tour="payments-cod" :title="__('admin.settings.payments.cod_title')" :description="__('admin.settings.payments.cod_text')">
            <x-admin.toggle name="cod_enabled" :label="__('admin.settings.payments.cod_enabled')" :checked="$values['cod_enabled'] ?? true" />
            <div class="max-w-xs">
                <x-admin.input name="cod_fee" type="number" step="0.01" min="0" :label="__('admin.settings.payments.cod_fee')" :value="$values['cod_fee'] ?? '0.00'" :prefix="config('store.base_currency')" dir="ltr" :help="__('admin.settings.payments.cod_fee_help')" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section data-tour="payments-stripe" :title="__('admin.settings.payments.stripe_title')" :description="__('admin.settings.payments.stripe_text')">
            @if (! $stripeConfigured)
                <x-admin.alert tone="warning" :title="__('admin.settings.payments.stripe_missing_title')">
                    {{ __('admin.credentials.stripe_add_below') }}
                </x-admin.alert>
            @else
                <x-admin.alert :tone="$stripeTestMode ? 'warning' : 'success'">
                    {{ $stripeTestMode ? __('admin.settings.payments.stripe_test_mode') : __('admin.settings.payments.stripe_live_mode') }}
                </x-admin.alert>
            @endif
            <x-admin.toggle name="stripe_enabled" :label="__('admin.settings.payments.stripe_enabled')" :description="__('admin.settings.payments.stripe_enabled_help')" :checked="$values['stripe_enabled'] ?? false" />

            <div class="space-y-1.5">
                <p class="text-sm font-medium">{{ __('admin.settings.payments.webhook_title') }}</p>
                <p class="text-[13px] text-muted">{{ __('admin.settings.payments.webhook_text') }}</p>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <code class="min-w-0 flex-1 truncate rounded-lg border border-line bg-canvas px-3 py-2 text-[13px]" dir="ltr">{{ $webhookUrl }}</code>
                    <x-admin.button type="button" variant="secondary" size="sm" data-copy="{{ $webhookUrl }}" x-on:click="navigator.clipboard.writeText($el.dataset.copy); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-show="! copied">{{ __('admin.media.copy_link') }}</span><span x-show="copied" x-cloak>{{ __('admin.media.copied') }}</span>
                    </x-admin.button>
                </div>
                <p class="text-[13px] {{ $webhookConfigured ? 'text-success-600' : 'text-muted' }}">
                    {{ $webhookConfigured ? __('admin.settings.payments.webhook_ok') : __('admin.settings.payments.webhook_missing') }}
                </p>
            </div>
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>

    {{-- Stripe account (separate form: keys are saved on their own, encrypted) --}}
    <x-admin.form-section data-tour="payments-keys" :title="__('admin.credentials.stripe_title')" :description="__('admin.credentials.stripe_text')" class="mt-8" id="stripe-account">
        <form method="POST" action="{{ route('admin.settings.credentials.update', 'stripe') }}" class="space-y-4">
            @csrf @method('PUT')
            @include('admin.settings.partials.credential-field', ['group' => 'stripe', 'field' => 'key', 'label' => __('admin.credentials.stripe_key'), 'placeholder' => 'pk_test_…'])
            @include('admin.settings.partials.credential-field', ['group' => 'stripe', 'field' => 'secret', 'label' => __('admin.credentials.stripe_secret'), 'placeholder' => 'sk_test_…'])
            @include('admin.settings.partials.credential-field', ['group' => 'stripe', 'field' => 'webhook_secret', 'label' => __('admin.credentials.stripe_webhook'), 'placeholder' => 'whsec_…'])
            <p class="text-xs text-muted">{{ __('admin.credentials.stripe_help') }}</p>
            <div class="flex flex-wrap items-center gap-2 border-t border-line pt-4">
                <x-admin.button>{{ __('admin.credentials.save') }}</x-admin.button>
            </div>
        </form>
        @if ($stripeConfigured)
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.settings.credentials.test', 'stripe') }}">@csrf<x-admin.button variant="secondary" icon="circle-check">{{ __('admin.credentials.test') }}</x-admin.button></form>
                <form method="POST" action="{{ route('admin.settings.credentials.forget', 'stripe') }}" x-data @submit="if (! confirm(@js(__('admin.credentials.remove_confirm')))) $event.preventDefault()">
                    @csrf @method('DELETE')<x-admin.button variant="ghost" class="text-danger-600">{{ __('admin.credentials.remove') }}</x-admin.button>
                </form>
            </div>
        @endif
    </x-admin.form-section>
@endsection
