@extends('storefront.layouts.app')

@section('title', __('store.account.orders'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-4xl px-4 pb-20 pt-10 sm:px-6">
        <x-store.breadcrumbs :items="[['label' => __('store.account.title'), 'url' => store_route('account.dashboard')], ['label' => __('store.account.orders')]]" />
        <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ __('store.account.orders') }}</h1>

        @if ($orders->isEmpty())
            <p class="mt-8 text-[15px] text-ink-soft">{{ __('store.account.no_orders') }}</p>
        @else
            <ul class="mt-8 divide-y divide-line border-y border-line">
                @foreach ($orders as $order)
                    @include('storefront.account.partials.order-row')
                @endforeach
            </ul>
            {{ $orders->links('components.store.pagination') }}
        @endif
    </div>
@endsection
