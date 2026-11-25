@extends('storefront.layouts.app')

{{--
    Order page in the FreshNest look — shown right after checkout and whenever the customer
    opens the order from their email or account. The timeline is the design's Track Order
    view (Order placed → Packed → Out for delivery → Delivered), driven by the order's real
    status (Admin → Orders: fulfil / mark delivered).
--}}
@section('freshnest', true)
@section('title', __('store.order.title', ['number' => $order->number]))
@section('noindex', true)

@php
    $stage = $order->stage();
    $placed = session('placed') && $stage !== 'awaiting_payment' && $stage !== 'cancelled';
    $reached = match ($stage) { 'delivered' => 4, 'shipped' => 3, 'cancelled' => 0, default => 1 };
    $steps = [
        [__('Order placed'), $order->placed_at->timezone(config('store.display_timezone'))->format('j M Y, g:i a')],
        [__('Packed'), __('Picked and packed at the depot.')],
        [__('Out for delivery'), $order->tracking_number ? __('store.order.tracking', ['carrier' => $order->carrier, 'number' => $order->tracking_number]) : __('Your driver is on the way.')],
        [__('Delivered'), __('Enjoy your groceries.')],
    ];
    $status = match ($stage) {
        'delivered' => __('Delivered'), 'shipped' => __('Out for delivery'), 'cancelled' => __('Cancelled'),
        'awaiting_payment' => __('Awaiting payment'), default => __('Confirmed'),
    };
    $money = fn ($m) => preg_replace('/^([^\d\s]+)\x{00A0}/u', '$1', price_order($m, $order));
    $label = 'font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--fn-muted);';
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:15px; color:var(--fn-muted);';
@endphp

@if (session('placed') && settings()->get('tracking.ga_measurement_id') && ! $order->awaitsOnlinePayment())
    @include('storefront.orders.purchase-event', ['order' => $order])
@endif

@section('content')
    @include('storefront.partials.page-band', [
        'title' => $placed ? __('Thank you, ').\Illuminate\Support\Str::before(trim((string) $order->ship_name).' ', ' ').'!' : 'Order '.$order->number,
        'crumb' => __('Order :number', ['number' => $order->number]),
        'crumbs' => auth('web')->check() ? [[__('My Orders'), store_route('account.orders')]] : [],
        'blurb' => $placed ? __('Order :number is confirmed. We’ve sent the details to :email.', ['number' => $order->number, 'email' => $order->email]) : null,
    ])

    <div class="wrap" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px); display:flex; flex-wrap:wrap; gap:24px; align-items:flex-start;">
      <div style="flex:1 1 320px; min-width:0; display:flex; flex-direction:column; gap:18px;">
        @if ($stage === 'cancelled')
          <p role="status" style="margin:0; padding:14px 18px; border-radius:14px; background:#FDECEA; color:#D4380D; font-weight:600;">{{ __('store.order.stages.cancelled') }}</p>
        @elseif ($order->awaitsOnlinePayment())
          <div style="background:var(--fn-accent-tint); border:1.5px solid #F3C7A8; border-radius:18px; padding:20px; display:flex; flex-direction:column; gap:12px;">
            <p style="margin:0; font-size:15px; color:var(--fn-ink);">{{ __('store.order.awaiting_payment') }}</p>
            @if (session('payment_error'))<p role="alert" style="margin:0; font-size:14px; color:#D4380D;">{{ session('payment_error') }}</p>@endif
            <form method="POST" action="{{ store_route('orders.pay', ['token' => $order->access_token]) }}" style="margin:0;">
              @csrf
              <button type="submit" style="padding:14px 22px; border:0; border-radius:999px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:15px; cursor:pointer;">{{ __('store.order.pay_now', ['total' => price_order($order->grand_total, $order)]) }}</button>
            </form>
          </div>
        @endif

        <div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:clamp(20px,2.6vw,30px); display:flex; flex-direction:column; gap:18px;">
          <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px;">
            <div><div style="{{ $label }}">{{ __('Order number') }}</div><div style="font-weight:700; font-size:15px; color:var(--fn-ink); margin-top:4px;">{{ $order->number }}</div></div>
            <div><div style="{{ $label }}">{{ __('Payment') }}</div><div style="font-size:15px; color:var(--fn-ink); margin-top:4px;">{{ __("store.order.methods.{$order->payment_method}") }}</div></div>
            <div><div style="{{ $label }}">{{ __('Delivery') }}</div><div style="font-size:15px; color:var(--fn-ink); margin-top:4px;">{{ $order->shipping_method_name ?: __('store.checkout.delivery') }}</div></div>
            <div><div style="{{ $label }}">{{ __('Placed') }}</div><div style="font-size:15px; color:var(--fn-ink); margin-top:4px;">{{ $order->placed_at->timezone(config('store.display_timezone'))->format('j M Y') }}</div></div>
          </div>
          <div style="padding-top:16px; border-top:1px solid var(--fn-line);">
            <div style="{{ $label }}">{{ __('Delivery address') }}</div>
            <p style="margin:6px 0 0; font-size:15px; line-height:1.6; color:var(--fn-ink);">
              {{ $order->ship_name }}<br>{{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>
              {{ $order->shipRegionLine() }}<br>
              <span dir="ltr">{{ $order->ship_phone }}</span>
            </p>
          </div>
          <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <a class="fh-ed87d8" href="{{ store_route('shop') }}" style="padding:13px 22px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14.5px;">{{ __('Keep shopping') }}</a>
            @auth('web')<a class="fh-74ffb8" href="{{ store_route('account.orders') }}" style="padding:13px 22px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); color:var(--fn-primary-dark); font-weight:700; font-size:14.5px;">{{ __('My orders') }}</a>@endauth
            <a class="fh-74ffb8" href="{{ store_route('orders.invoice', ['token' => $order->access_token]) }}" target="_blank" rel="noopener" style="padding:13px 22px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); color:var(--fn-primary-dark); font-weight:700; font-size:14.5px;">{{ __('store.order.invoice') }}</a>
          </div>
        </div>

        @php
            $returnService = app(\App\Services\Returns\ReturnService::class);
            $orderReturns = $order->returns()->withSum('items as units', 'quantity')->latest('id')->get();
        @endphp
        @if ($orderReturns->isNotEmpty() || $returnService->canRequest($order))
          <section aria-labelledby="returns-heading" style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:clamp(20px,2.6vw,30px); display:flex; flex-direction:column; gap:12px;">
            <h2 id="returns-heading" style="margin:0; font-family:'Plus Jakarta Sans',sans-serif; font-size:18px; color:var(--fn-ink);">{{ __('store.returns.section') }}</h2>
            @foreach ($orderReturns as $return)
              <div style="display:flex; justify-content:space-between; gap:10px; font-size:14px; color:var(--fn-ink);">
                <span>{{ __("store.returns.resolutions.{$return->resolution}") }} · {{ trans_choice('store.returns.units', $return->units ?? 0, ['count' => $return->units ?? 0]) }}</span>
                <span style="padding:4px 10px; border-radius:999px; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-size:12.5px; font-weight:700;">{{ __("store.returns.status.{$return->status}") }}</span>
              </div>
            @endforeach
            @if ($returnService->canRequest($order))
              <p style="margin:0; font-size:13.5px; color:var(--fn-muted);">{{ __('store.returns.until', ['date' => $order->delivered_at->addDays($returnService->windowDays())->translatedFormat('j F Y')]) }}</p>
              <a class="fh-151e18" href="{{ store_route('returns.create', ['token' => $order->access_token]) }}" style="align-self:flex-start; padding:12px 20px; border-radius:999px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:14px;">{{ __('store.returns.request') }}</a>
            @endif
          </section>
        @endif
      </div>

      <div style="flex:2 1 460px; min-width:0; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:clamp(20px,2.6vw,30px); display:flex; flex-direction:column; gap:18px;">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:10px;">
          <div style="display:flex; flex-direction:column; gap:4px;"><strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:20px; color:var(--fn-ink);">{{ $order->number }}</strong><span style="font-size:14px; color:var(--fn-muted);">{{ $order->placed_at->timezone(config('store.display_timezone'))->format('M j, Y') }} · {{ $money($order->grand_total) }}</span></div>
          <span style="align-self:flex-start; padding:6px 12px; border-radius:999px; background:{{ $stage === 'cancelled' ? '#FDECEA' : 'var(--fn-primary-tint)' }}; color:{{ $stage === 'cancelled' ? '#D4380D' : 'var(--fn-primary-dark)' }}; font-size:13px; font-weight:700;">{{ $status }}</span>
        </div>
        @if ($stage !== 'cancelled')
        <div style="display:flex; flex-direction:column; gap:0;" aria-label="{{ __('Order progress') }}">
          @foreach ($steps as $i => [$text, $note])
            @php $done = $i < $reached; @endphp
            <div style="display:flex; gap:14px;">
              <div style="display:flex; flex-direction:column; align-items:center;">
                <span style="width:18px; height:18px; border-radius:50%; background:{{ $done ? 'var(--fn-primary)' : '#FFFFFF' }}; border:3px solid {{ $done ? '#CFE8D2' : '#DCE8DD' }}; flex:0 0 auto;"></span>
                <span style="width:2px; flex:1 1 auto; min-height:34px; background:{{ $i === 3 ? 'transparent' : ($i < $reached - 1 ? 'var(--fn-primary)' : 'var(--fn-primary-tint)') }};"></span>
              </div>
              <div style="display:flex; flex-direction:column; gap:2px; padding-bottom:18px;"><strong style="font-size:15px; color:{{ $done ? 'var(--fn-ink)' : '#8A968A' }};">{{ $text }}</strong><span style="font-size:13.5px; color:var(--fn-muted);">{{ $note }}</span></div>
            </div>
          @endforeach
        </div>
        @endif

        <div style="display:flex; flex-direction:column; gap:4px; padding-top:6px; border-top:1px solid var(--fn-line);">
          @foreach ($order->items as $item)
            <div style="display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:1px solid #F2F6F2;">
              @if ($item->image)<img src="{{ $item->image }}" alt="" loading="lazy" style="width:56px; height:56px; border-radius:12px; object-fit:cover; background:var(--fn-primary-tint); flex:0 0 auto;">@else<span style="width:56px; height:56px; border-radius:12px; background:var(--fn-primary-tint); flex:0 0 auto;"></span>@endif
              <div style="flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:3px;">
                <strong style="font-size:15px; color:var(--fn-ink);">{{ $item->name }}</strong>
                <span style="font-size:13px; color:var(--fn-muted);">{{ __('Qty') }} {{ $item->quantity }}@if ($item->variant_title) · {{ $item->variant_title }}@endif</span>
                @if ($item->optionsLabel())<span style="font-size:12.5px; color:var(--fn-muted);">{{ $item->optionsLabel() }}</span>@endif
              </div>
              <strong style="font-size:15px; color:var(--fn-ink);">{{ $money($item->line_total) }}</strong>
            </div>
          @endforeach
        </div>

        <div style="display:flex; flex-direction:column; gap:10px;">
          <div style="{{ $row }}"><span>{{ __('Subtotal') }}</span><span style="color:var(--fn-ink);">{{ $money($order->subtotal) }}</span></div>
          @foreach ($order->dealLines() as $deal)
            <div style="{{ $row }}"><span>{{ __('store.deals.line', ['name' => $deal['label']]) }}</span><span style="color:var(--fn-primary);">−{{ $money($deal['amount']) }}</span></div>
          @endforeach
          @unless ($order->discount_total->isZero())
            <div style="{{ $row }}"><span>{{ __('store.discount.line', ['code' => $order->discount_code]) }}</span><span style="color:var(--fn-primary);">−{{ $money($order->discount_total) }}</span></div>
          @endunless
          <div style="{{ $row }}"><span>{{ $order->shipping_method_name ?: __('Delivery') }}</span><span style="color:var(--fn-ink);">{{ $order->shipping_total->isZero() ? __('store.checkout.free') : $money($order->shipping_total) }}</span></div>
          @unless ($order->payment_fee->isZero())
            <div style="{{ $row }}"><span>{{ __('store.checkout.cod_fee') }}</span><span style="color:var(--fn-ink);">{{ $money($order->payment_fee) }}</span></div>
          @endunless
          @if (! $order->prices_include_tax && $order->tax_label)
            <div style="{{ $row }}"><span>{{ $order->tax_label }}</span><span style="color:var(--fn-ink);">{{ $money($order->tax_total) }}</span></div>
          @endif
          <div style="display:flex; justify-content:space-between; font-family:'Plus Jakarta Sans',sans-serif; font-size:19px; font-weight:800; color:var(--fn-ink); padding-top:14px; border-top:1px dashed var(--fn-primary-pale);"><span>{{ __('Total') }}</span><span>{{ $money($order->grand_total) }}</span></div>
          @if ($order->prices_include_tax && $order->tax_label)
            <p style="margin:0; font-size:13px; color:var(--fn-muted);">{{ __('store.order.tax_included', ['tax' => $order->tax_label, 'amount' => price_order($order->tax_total, $order)]) }}</p>
          @endif
        </div>
      </div>
    </div>
@endsection
