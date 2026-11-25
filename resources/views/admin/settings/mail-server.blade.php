@extends('admin.settings.layout')

@section('title', __('admin.settings.mail_server.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.mail_server.title')" :back="route('admin.settings.index')" :subtitle="__('admin.settings.mail_server.subtitle')" />

    @php
        $credentials = app(\App\Services\Settings\Credentials::class);
        // Queued mail only goes out while a worker runs. If jobs are piling up, say so.
        $queued = config('queue.default') !== 'sync' && \Illuminate\Support\Facades\Schema::hasTable('jobs')
            ? \Illuminate\Support\Facades\DB::table('jobs')->count() : 0;
    @endphp

    @if ($queued > 0)
        <x-admin.alert tone="warning" class="mt-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>{{ trans_choice('admin.settings.mail_server.queue_stuck', $queued, ['count' => $queued]) }}</span>
                <form method="POST" action="{{ route('admin.settings.mail-server.run-queue') }}">
                    @csrf
                    <x-admin.button variant="secondary" size="sm">{{ __('admin.settings.mail_server.queue_send_now') }}</x-admin.button>
                </form>
            </div>
        </x-admin.alert>
    @endif

    <x-admin.form-section :title="__('admin.settings.mail_server.smtp_title')" :description="__('admin.settings.mail_server.smtp_text')" class="mt-8">
        <form method="POST" action="{{ route('admin.settings.credentials.update', 'smtp') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'host', 'label' => __('admin.settings.mail_server.host'), 'placeholder' => 'smtp.gmail.com'])
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'port', 'label' => __('admin.settings.mail_server.port'), 'placeholder' => '587'])
            </div>

            <div>
                <label for="smtp-encryption" class="mb-1 block text-[13px] font-medium">{{ __('admin.settings.mail_server.encryption') }}</label>
                @php $encryption = (string) config('mail.mailers.smtp.encryption') ?: 'tls'; @endphp
                <select id="smtp-encryption" name="encryption" class="admin-field" @disabled($credentials->fromServer('smtp', 'encryption'))>
                    <option value="tls" @selected($encryption === 'tls')>{{ __('admin.settings.mail_server.tls') }}</option>
                    <option value="ssl" @selected($encryption === 'ssl')>{{ __('admin.settings.mail_server.ssl') }}</option>
                    <option value="none" @selected($encryption === 'none')>{{ __('admin.settings.mail_server.none') }}</option>
                </select>
                <p class="mt-1 text-xs text-muted">{{ __('admin.settings.mail_server.encryption_help') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'username', 'label' => __('admin.settings.mail_server.username'), 'placeholder' => 'info@example.com'])
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'password', 'label' => __('admin.settings.mail_server.password'), 'placeholder' => ''])
            </div>
            <p class="text-xs text-muted">{{ __('admin.settings.mail_server.gmail_help') }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'from_address', 'label' => __('admin.settings.mail_server.from_address'), 'placeholder' => 'info@example.com'])
                @include('admin.settings.partials.credential-field', ['group' => 'smtp', 'field' => 'from_name', 'label' => __('admin.settings.mail_server.from_name'), 'placeholder' => settings()->trans('general.store_name')])
            </div>

            <x-admin.button>{{ __('admin.credentials.save') }}</x-admin.button>
        </form>
    </x-admin.form-section>

    {{-- Send a test email to any address; a failure is explained in plain words with the fix. --}}
    @php
        $mailTest = session('mail_test');
        $testTo = old('to', $mailTest['to'] ?? auth('admin')->user()?->email);
    @endphp
    <x-admin.form-section id="mail-test" :title="__('admin_mail.test_title')" :description="__('admin_mail.test_text')" class="mt-8" style="scroll-margin-top:5rem">
        <form method="POST" action="{{ route('admin.settings.credentials.test', 'smtp') }}" class="space-y-4">
            @csrf
            <div>
                <label for="test-to" class="mb-1 block text-[13px] font-medium">{{ __('admin_mail.test_to') }}</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="test-to" name="to" type="email" required maxlength="190" autocomplete="email" dir="ltr" class="admin-field w-full" value="{{ $testTo }}">
                    <x-admin.button class="shrink-0"><x-icon name="send" class="size-4" /> {{ __('admin_mail.send_test') }}</x-admin.button>
                </div>
                <x-admin.field-error name="to" />
                <p class="mt-1 text-xs text-muted">{{ __('admin_mail.test_to_help') }}</p>
            </div>

            @if ($mailTest && ($mailTest['ok'] ?? false))
                <x-admin.alert tone="success" :title="__('admin_mail.sent_title', ['email' => $mailTest['to']])">
                    {{ __('admin_mail.sent_text', ['seconds' => $mailTest['seconds'] ?? '–']) }}
                </x-admin.alert>
            @elseif ($mailTest)
                <x-admin.alert tone="danger" :title="__('admin_mail.failed_title')">
                    <p data-mail-reason>{{ $mailTest['reason'] ?? '' }}</p>
                    @if (! empty($mailTest['fixes']))
                        <p class="mt-2 font-medium text-ink">{{ __('admin_mail.how_to_fix') }}</p>
                        <ul class="mt-1 list-disc space-y-1 ps-5">
                            @foreach ($mailTest['fixes'] as $fix)
                                <li>{{ $fix }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($mailTest['details']))
                        <details class="mt-2 text-xs text-muted">
                            <summary class="cursor-pointer">{{ __('admin_mail.details') }}</summary>
                            <code class="mt-1 block" style="word-break:break-all" dir="ltr">{{ $mailTest['details'] }}</code>
                        </details>
                    @endif
                </x-admin.alert>
            @endif
        </form>
    </x-admin.form-section>

    <x-admin.form-section :title="__('admin.settings.mail_server.recaptcha_title')" :description="__('admin.settings.mail_server.recaptcha_text')" class="mt-8">
        <form method="POST" action="{{ route('admin.settings.credentials.update', 'recaptcha') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                @include('admin.settings.partials.credential-field', ['group' => 'recaptcha', 'field' => 'site_key', 'label' => __('admin.settings.mail_server.site_key'), 'placeholder' => '6Lc…'])
                @include('admin.settings.partials.credential-field', ['group' => 'recaptcha', 'field' => 'secret', 'label' => __('admin.settings.mail_server.secret_key'), 'placeholder' => ''])
            </div>

            <x-admin.alert :tone="app(\App\Services\Security\Recaptcha::class)->enabled() ? 'success' : 'info'">
                {{ app(\App\Services\Security\Recaptcha::class)->enabled() ? __('admin.settings.mail_server.recaptcha_on') : __('admin.settings.mail_server.recaptcha_off') }}
            </x-admin.alert>

            <p class="text-xs text-muted">{{ __('admin.settings.mail_server.recaptcha_help') }}</p>

            <x-admin.button>{{ __('admin.credentials.save') }}</x-admin.button>
        </form>
    </x-admin.form-section>
@endsection
