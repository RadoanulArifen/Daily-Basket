<li>
    <a href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" class="flex flex-wrap items-center gap-x-6 gap-y-1 py-4 hover:bg-wool-soft/40">
        <span class="w-28 font-medium" dir="ltr">{{ $order->number }}</span>
        <span class="text-[15px] text-ink-soft">{{ $order->placed_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y') }}</span>
        <span class="text-[15px]">{{ __("store.order.stages.{$order->stage()}") }}</span>
        <span class="ms-auto text-[15px] font-medium">{{ price_order($order->grand_total, $order) }}</span>
        <x-icon name="chevron-right" class="size-4 text-stone rtl:rotate-180" />
    </a>
</li>
