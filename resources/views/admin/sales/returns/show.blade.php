@extends('admin.layouts.app')

@section('title', $return->number)

@section('content')
    @php $order = $return->order; $tz = config('store.display_timezone'); @endphp
    <x-admin.page-header :title="$return->number" :back="route('admin.returns.index')"
        :subtitle="__('admin.returns.requested_on', ['date' => $return->created_at->timezone($tz)->translatedFormat('j F Y, H:i'), 'order' => $order->number])">
        <x-slot:actions>
            <x-admin.badge>{{ __("store.returns.resolutions.{$return->resolution}") }}</x-admin.badge>
            <x-admin.badge :tone="$return->tone()">{{ __("admin.returns.status.{$return->status}") }}</x-admin.badge>
            <x-admin.button :href="route('admin.orders.show', $order)" variant="secondary">{{ __('admin.returns.view_order') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @error('status')<x-admin.alert tone="danger" class="mb-6">{{ $message }}</x-admin.alert>@enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :padding="false">
                <ul class="divide-y divide-line">
                    @foreach ($return->items as $item)
                        <li class="flex items-center gap-4 px-4 py-3 sm:px-5">
                            @if ($item->orderItem->image)<img src="{{ $item->orderItem->image }}" alt="" class="size-12 rounded-lg border border-line object-cover">@endif
                            <span class="min-w-0 flex-1 text-sm"><span class="block font-medium">{{ $item->orderItem->name }}</span><span class="block text-[13px] text-muted">{{ $item->orderItem->variant_title }} <span dir="ltr">{{ $item->orderItem->sku }}</span></span></span>
                            <span class="text-sm">× {{ $item->quantity }}</span>
                            <span class="w-28 text-end text-sm font-medium" dir="ltr">{{ price_admin($item->orderItem->unit_price->multiply($item->quantity)) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>

            <x-admin.card :title="__('admin.returns.next_step')">
                @php
                    $steps = match ($return->status) {
                        'requested' => [['approve', 'primary'], ['reject', 'danger']],
                        'approved' => [['receive', 'primary'], ['reject', 'danger']],
                        'received' => [['complete', 'primary']],
                        default => [],
                    };
                @endphp
                @if (! $steps)
                    <p class="text-sm text-muted">{{ __('admin.returns.finished') }}</p>
                @else
                    <p class="mb-4 text-[13px] text-muted">{{ __("admin.returns.help.{$return->status}") }}</p>
                    @foreach ($steps as [$action, $variant])
                        <form method="POST" action="{{ route('admin.returns.update', $return) }}" class="mb-3 flex flex-wrap items-end gap-3">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="{{ $action }}">
                            @if ($action === 'complete' && $return->resolution === 'refund')
                                @php $due = app(\App\Services\Returns\ReturnService::class)->refundable($return); @endphp
                                @if ($due->minor > 0 && $return->order->refundable()->minor > 0)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="hidden" name="refund" value="0">
                                        <input type="checkbox" name="refund" value="1" checked class="size-4 accent-brand-600">
                                        {{ __('admin.returns.refund_now', ['amount' => price_admin($due)]) }}
                                    </label>
                                @endif
                            @endif
                            @if ($action === 'receive')
                                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="restock" value="0"><input type="checkbox" name="restock" value="1" checked class="size-4 accent-brand-600"> {{ __('admin.returns.restock') }}</label>
                            @else
                                <div class="min-w-60 flex-1"><x-admin.input name="note" :id="'note-'.$action" :label="__('admin.returns.note_'.$action)" maxlength="1000" /></div>
                            @endif
                            <x-admin.button :variant="$variant">{{ __("admin.returns.actions.{$action}") }}</x-admin.button>
                        </form>
                    @endforeach
                @endif
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.returns.details')">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-muted">{{ __('store.returns.reason') }}</dt><dd>{{ __("store.returns.reasons.{$return->reason}") }}</dd></div>
                    @if ($return->customer_note)<div><dt class="text-muted">{{ __('admin.returns.customer_note') }}</dt><dd class="whitespace-pre-line">{{ $return->customer_note }}</dd></div>@endif
                    @if ($return->admin_note)<div><dt class="text-muted">{{ __('admin.returns.admin_note') }}</dt><dd class="whitespace-pre-line">{{ $return->admin_note }}</dd></div>@endif
                    <div><dt class="text-muted">{{ __('admin.orders.customer') }}</dt><dd>{{ $order->ship_name }} · <a href="mailto:{{ $order->email }}" class="text-brand-600 hover:underline">{{ $order->email }}</a></dd></div>
                    @if ($return->restocked)<div class="text-success-600">{{ __('admin.returns.restocked') }}</div>@endif
                </dl>
            </x-admin.card>
        </div>
    </div>
@endsection
