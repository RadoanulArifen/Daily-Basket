{{-- Settings area: a left-hand menu beside every settings page (filtered by the admin's role). --}}
@extends('admin.layouts.app')

@section('fullWidth', true)

@php
    $me = auth('admin')->user();
    $groups = [
        [
            ['general', 'store', 'admin.settings.general.edit', 'admin.settings.general.*'],
            ['payments', 'credit-card', 'admin.settings.payments.edit', 'admin.settings.payments.*'],
            ['checkout', 'shopping-bag', 'admin.settings.checkout.edit', 'admin.settings.checkout.*'],
            ['shipping', 'truck', 'admin.settings.shipping.edit', 'admin.settings.shipping.*'],
            ['tax', 'receipt', 'admin.settings.tax.edit', 'admin.settings.tax.*'],
            ['currencies', 'coins', 'admin.settings.currencies.index', 'admin.settings.currencies.*'],
            ['notifications', 'mail', 'admin.settings.notifications.edit', 'admin.settings.notifications.*'],
            ['mail_server', 'send', 'admin.settings.mail-server.edit', 'admin.settings.mail-server.*'],
            ['tracking', 'chart-column', 'admin.settings.tracking.edit', 'admin.settings.tracking.*'],
            ['backup', 'database', 'admin.settings.backup.edit', 'admin.settings.backup.*'],
        ],
        [
            ['admins', 'users', 'admin.admins.index', 'admin.admins.*'],
            ['activity', 'clipboard-list', 'admin.activity', 'admin.activity'],
            ['account', 'user-cog', 'admin.account.edit', 'admin.account.*'],
        ],
    ];
    $allowed = fn (string $route) => ! ($area = \App\Enums\AdminRole::areaForRoute($route)) || $me?->mayAccess($area);
@endphp

@section('content')
    <div class="grid gap-5 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
        <nav aria-label="{{ __('admin.nav.settings') }}" class="min-w-0 lg:sticky lg:top-20 lg:self-start">
            <p class="mb-2 hidden px-2 text-xl font-bold tracking-tight text-ink-strong lg:block">{{ __('admin.nav.settings') }}</p>
            <div class="flex gap-1 overflow-x-auto pb-1 [scrollbar-width:none] lg:block lg:space-y-4 lg:overflow-visible">
                @foreach ($groups as $group)
                    <ul class="flex gap-1 lg:block lg:space-y-px">
                        @foreach ($group as [$key, $icon, $route, $active])
                            @continue(! $allowed($route))
                            @php $isActive = request()->routeIs($active); @endphp
                            <li class="shrink-0">
                                <a href="{{ route($route) }}" @if ($isActive) aria-current="page" @endif
                                   @class([
                                       'flex h-8 items-center gap-2.5 rounded-lg px-2 text-[13px] whitespace-nowrap transition',
                                       'bg-surface font-semibold text-ink-strong shadow-card' => $isActive,
                                       'font-medium text-ink-soft hover:bg-black/5 hover:text-ink-strong' => ! $isActive,
                                   ])>
                                    <x-icon :name="$icon" class="size-4 shrink-0" />{{ __("admin.settings.menu.{$key}") }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </nav>

        <div class="min-w-0 max-w-[56rem]">
            @yield('settings')
        </div>
    </div>
@endsection
