{{-- Item rows: $lines = collection of [OrderItem, quantity] --}}
<ul class="mt-3 divide-y divide-line border-t border-line">
    @foreach ($lines as [$item, $qty])
        <li class="flex items-center gap-3 px-4 py-3">
            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-subdued">
                @if ($item->image)<img src="{{ $item->image }}" alt="" class="size-full object-cover">@else<x-icon name="image" class="size-4 text-muted" />@endif
            </span>
            <span class="min-w-0 flex-1">
                @if ($item->product_id)
                    <a href="{{ route('admin.products.edit', $item->product_id) }}" class="block truncate text-[13px] font-medium text-brand-600 hover:underline">{{ $item->name }}</a>
                @else
                    <span class="block truncate text-[13px] font-medium">{{ $item->name }}</span>
                @endif
                @if ($item->optionsLabel())<span class="block text-xs text-muted">{{ $item->optionsLabel() }}</span>@endif
                <span class="block text-xs text-muted">@if ($item->variant_title){{ $item->variant_title }} · @endif @if ($item->sku)<span dir="ltr">{{ $item->sku }}</span>@endif</span>
            </span>
            <span class="text-[13px] whitespace-nowrap text-ink-soft" dir="ltr">{{ price_admin($item->unit_price) }} × {{ $qty }}</span>
            <span class="w-24 text-end text-[13px] whitespace-nowrap" dir="ltr">{{ price_admin($item->unit_price->multiply($qty)) }}</span>
        </li>
    @endforeach
</ul>
