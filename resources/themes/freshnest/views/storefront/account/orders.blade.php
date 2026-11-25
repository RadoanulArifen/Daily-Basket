@extends('storefront.account.layout')

{{-- My Orders in the design's cards: number, date, items, status, thumbnails, total,
     View and Reorder (the platform's reorder). Grouped by year like the design. --}}
@section('title', __('My Orders'))
@section('account_tab', 'orders')

@section('account')
@php
    $money = fn ($m, $o) => preg_replace('/^([^\d\s]+)\x{00A0}/u', '$1', price_order($m, $o));
    $pill = fn ($stage) => match ($stage) {
        'delivered' => [__('Delivered'), 'var(--fn-primary-tint)', 'var(--fn-primary-dark)'], 'shipped' => [__('Out for delivery'), 'var(--fn-accent-tint)', 'var(--fn-accent-dark)'],
        'cancelled' => [__('Cancelled'), '#FDECEA', '#D4380D'], 'awaiting_payment' => [__('Awaiting payment'), 'var(--fn-accent-tint)', 'var(--fn-accent-dark)'], default => [__('Confirmed'), 'var(--fn-primary-tint)', 'var(--fn-primary-dark)'],
    };
@endphp
            @if ($orders->isEmpty())
              <div style="padding:40px; text-align:center; background:#FFFFFF; border:1px dashed #D7E3D8; border-radius:20px; color:var(--fn-muted);">
                {{ __('No orders yet.') }} <a href="{{ store_route('shop') }}" style="font-weight:700;">{{ __('Start shopping') }}</a>
              </div>
            @endif
            @foreach ($orders->getCollection()->groupBy(fn ($o) => $o->placed_at->format('Y')) as $year => $group)
              <div style="display:flex; flex-direction:column; gap:12px;">
                <h3 style="display:flex; align-items:center; gap:12px; margin:6px 0 0; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:17px; color:var(--fn-ink);">{{ $year }}<span style="font-size:12.5px; font-weight:600; color:var(--fn-muted);">{{ $group->count() }} {{ $group->count() === 1 ? __('order') : __('orders') }}</span><span style="flex:1 1 auto; height:1px; background:#E4EAE4;"></span></h3>
                @foreach ($group as $order)
                  @php [$status, $sBg, $sColor] = $pill($order->stage()); @endphp
                  <div style="display:flex; flex-direction:column; gap:14px; padding:clamp(16px,2vw,22px); background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                      <span style="display:flex; flex-direction:column; gap:3px;">
                        <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:15.5px;">{{ __('Order #') }}{{ $order->number }}</strong>
                        <span style="font-size:13px; color:var(--fn-muted);">{{ __('Placed') }} {{ $order->placed_at->timezone(config('store.display_timezone'))->format('M j, Y') }} · {{ $order->itemCount() }} {{ $order->itemCount() === 1 ? __('item') : __('items') }} · {{ $order->placed_at->diffForHumans() }}</span>
                      </span>
                      <span style="display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border-radius:999px; background:{{ $sBg }}; color:{{ $sColor }}; font-size:12.5px; font-weight:700;"><span style="width:7px; height:7px; border-radius:50%; background:{{ $sColor }};"></span>{{ $status }}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                      @foreach ($order->items->take(4) as $item)
                        <span title="{{ $item->name }}" style="width:54px; height:54px; border-radius:12px; background:var(--fn-primary-tint){{ $item->image ? ' url('.$item->image.')' : '' }} center/cover no-repeat;"></span>
                      @endforeach
                      <span style="flex:1 1 200px; min-width:0; font-size:13.5px; color:var(--fn-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $order->items->pluck('name')->implode(', ') }}</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding-top:12px; border-top:1px solid #F0F3F0;">
                      <span style="font-size:14px; color:var(--fn-muted);">{{ __('Total') }} <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:17px; color:var(--fn-ink); margin-left:6px;">{{ $money($order->grand_total, $order) }}</strong></span>
                      <span style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="fh-74ffb8" href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" style="height:40px; padding:0 18px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); color:var(--fn-primary-dark); font-weight:700; font-size:13.5px; display:inline-flex; align-items:center;">{{ __('View details') }}</a>
                        <form method="POST" action="{{ store_route('account.orders.reorder', ['order' => $order->id]) }}" style="margin:0;">@csrf
                          <button class="fh-92c14d" type="submit" style="height:40px; padding:0 18px; border:0; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:13.5px; cursor:pointer;">{{ __('Reorder') }}</button>
                        </form>
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>
            @endforeach
            @if ($orders->hasPages())
              <nav aria-label="{{ __('Pages') }}" style="display:flex; gap:8px; justify-content:center;">
                @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                  @php $on = $page === $orders->currentPage(); @endphp
                  <a href="{{ $url }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:42px; height:42px; border-radius:12px; border:1.5px solid {{ $on ? 'var(--fn-primary)' : 'var(--fn-primary-tint)' }}; background:{{ $on ? 'var(--fn-primary)' : '#FFFFFF' }}; color:{{ $on ? '#FFFFFF' : 'var(--fn-ink)' }}; font-weight:600;">{{ $page }}</a>
                @endforeach
              </nav>
            @endif
@endsection
