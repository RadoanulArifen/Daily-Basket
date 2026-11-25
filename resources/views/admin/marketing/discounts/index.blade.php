@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.discounts.title'))

@section('content')
    <x-admin.page-header :title="__('admin.discounts.title')" :subtitle="__('admin.discounts.subtitle')">
        <x-slot:actions><x-admin.button :href="route('admin.discounts.create')" icon="plus">{{ __('admin.discounts.add') }}</x-admin.button></x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($discounts->isEmpty())
            <x-admin.empty-state icon="badge-percent" :title="__('admin.discounts.empty_title')" :text="__('admin.discounts.empty_text')">
                <x-admin.button :href="route('admin.discounts.create')" icon="plus">{{ __('admin.discounts.add') }}</x-admin.button>
            </x-admin.empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($discounts as $discount)
                    @php $state = $discount->state(); @endphp
                    <li>
                        <a href="{{ route('admin.discounts.edit', $discount) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 hover:bg-canvas/50 sm:px-5">
                            <span class="w-40 font-mono text-sm font-semibold" dir="ltr">{{ $discount->code }}</span>
                            <span class="min-w-0 flex-1 text-sm text-ink-soft">{{ $discount->summary() }}@if ($discount->min_subtotal) · {{ __('admin.discounts.min_short', ['amount' => price_admin($discount->min_subtotal)]) }}@endif</span>
                            <span class="text-[13px] text-muted">{{ trans_choice('admin.discounts.used', $discount->used_count, ['count' => $discount->used_count]) }}@if ($discount->usage_limit) / {{ $discount->usage_limit }}@endif</span>
                            <x-admin.badge :tone="match ($state) { 'live' => 'success', 'scheduled' => 'info', default => 'neutral' }">{{ __("admin.discounts.states.{$state}") }}</x-admin.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
            {{ $discounts->links('components.admin.pagination') }}
        @endif
    </x-admin.card>
@endsection
