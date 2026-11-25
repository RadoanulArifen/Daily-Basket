@extends('admin.layouts.app')

@section('title', __('admin.customers.title'))
@section('fullWidth', true)

@section('content')
    <x-admin.page-header :title="__('admin.customers.title')" :subtitle="trans_choice('admin.customers.count', $total, ['count' => $total])" />

    <div class="overflow-hidden rounded-xl bg-surface shadow-card">
        <x-admin.index-filters resource="customers" route="admin.customers.index" tab-param="segment" :placeholder="__('admin.customers.search')"
            :tabs="collect(\App\Http\Controllers\Admin\Insights\CustomerController::SEGMENTS)->map(fn ($s) => ['key' => $s, 'label' => __('admin.customers.segments.'.$s)])->all()"
            :sorts="collect(\App\Http\Controllers\Admin\Insights\CustomerController::SORTS)->mapWithKeys(fn ($s) => [$s => __('admin.customers.sort.'.$s)])->all()">
            <x-slot:filters>
                <x-admin.filter-pill name="country" :label="__('admin.customers.filters.country')" :options="\App\Support\Countries::allowed()" />
            </x-slot:filters>
        </x-admin.index-filters>

        @if ($customers->isEmpty())
            <x-admin.empty-state icon="users" :title="__('admin.customers.empty_title')" :text="__('admin.customers.empty_text')" />
        @else
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.customers.cols.name') }}</th>
                            <th>{{ __('admin.customers.cols.email') }}</th>
                            <th>{{ __('admin.customers.cols.location') }}</th>
                            <th class="text-end">{{ __('admin.customers.cols.orders') }}</th>
                            <th class="text-end">{{ __('admin.customers.cols.spent') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            @php $url = route('admin.customers.show', $customer); $address = $customer->addresses->first(); @endphp
                            <tr class="cursor-pointer" x-data @click="if (! $event.target.closest('a')) window.location = @js($url)">
                                <td class="whitespace-nowrap">
                                    <a href="{{ $url }}" class="font-semibold text-ink-strong hover:underline">{{ $customer->name }}</a>
                                    @unless ($customer->is_active)<x-admin.badge tone="danger" class="ms-1.5">{{ __('admin.customers.blocked_badge') }}</x-admin.badge>@endunless
                                </td>
                                <td class="max-w-56 truncate text-ink-soft">{{ $customer->email }}</td>
                                <td class="whitespace-nowrap text-ink-soft">{{ $address ? $address->regionLine() : '—' }}</td>
                                <td class="text-end text-ink-soft">{{ trans_choice('admin.customers.orders', $customer->sales_count, ['count' => $customer->sales_count]) }}</td>
                                <td class="text-end whitespace-nowrap" dir="ltr">{{ price_admin(\App\Support\Money::of(number_format((float) $customer->spent, 2, '.', ''), config('store.base_currency'))) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $customers->links('components.admin.pagination') }}
        @endif
    </div>
@endsection
