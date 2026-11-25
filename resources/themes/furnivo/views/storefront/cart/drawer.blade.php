{{-- Slide-out bag contents — markup and styles from the design. Loaded into the drawer
     (layouts/app) each time it opens; quantity and remove call the bag's own actions. --}}
<div style="display: flex; align-items: center; justify-content: space-between; padding: 22px 24px; border-bottom: 1px solid rgb(236, 236, 236);">
    <div id="cart-drawer-title" style="font-size: 13px; letter-spacing: 0.18em; text-transform: uppercase; color: rgb(32, 32, 32);">Your cart · <span>{{ $summary->count }}</span></div>
    <button type="button" @click="close()" aria-label="{{ __('store.common.close') }}" style="border: none; background: transparent; font-size: 22px; line-height: 1; color: rgb(32, 32, 32); cursor: pointer;">✕</button>
</div>

@if (empty($summary->lines))
    <div style="flex: 1 1 0%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 40px; text-align: center;">
        <p style="margin: 0px; font-size: 16px; color: rgb(139, 135, 127);">Your cart is empty.</p>
        <button type="button" @click="close()" style="border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; padding: 14px 28px; cursor: pointer;">{{ __('store.theme.continue_browsing') }}</button>
    </div>
@else
    <div class="no-sb" style="flex: 1 1 0%; overflow-y: auto; padding: 8px 24px;">
        @foreach ($summary->notices as $notice)
            <p role="status" style="margin: 12px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $notice }}</p>
        @endforeach
        @foreach ($summary->lines as $line)
            @php
                $id = $line->item->id;
                $qty = $line->item->quantity;
                $media = $line->variant->image ?? $line->product->media->first();
                $url = store_route('products.show', ['slug' => $line->product->slug]);
                $atMax = $line->available !== null && $qty >= $line->available;
            @endphp
            <div style="display: grid; grid-template-columns: 72px 1fr auto; gap: 14px; padding: 20px 0px; border-bottom: 1px solid rgb(241, 239, 236); align-items: center;">
                <a href="{{ $url }}" style="width: 72px; height: 72px; overflow: hidden; background: rgb(239, 236, 231); position: relative; display: block;">
                    @if ($media)<img src="{{ $media->url('thumb') }}" alt="{{ $line->product->name }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                </a>
                <div style="min-width: 0px;">
                    @if ($line->product->brandRecord)
                        <div style="font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 3px;"><span>{{ $line->product->brandRecord->name }}</span></div>
                    @endif
                    <div style="font-size: 15px; font-weight: 600; color: rgb(32, 32, 32); margin-bottom: 8px; line-height: 1.25;"><a href="{{ $url }}" style="color: inherit;"><span>{{ $line->product->name }}</span></a></div>
                    @if ($title = $line->variant->title())<div style="font-size: 12px; color: rgb(139, 135, 127); margin: -4px 0px 8px;">{{ $title }}</div>@endif
                    @if ($options = $line->item->optionsLabel())<div style="font-size: 12px; line-height: 1.4; color: rgb(139, 135, 127); margin: -4px 0px 8px;">{{ $options }}</div>@endif
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="display: flex; align-items: center; border: 1px solid rgb(217, 213, 207);">
                            <button type="button" @click="setQuantity({{ $id }}, {{ $qty - 1 }})" :disabled="busy" aria-label="{{ __('store.product.decrease') }}" style="width: 28px; height: 30px; border: none; background: transparent; cursor: pointer; font-size: 15px;">–</button>
                            <span style="width: 26px; text-align: center; font-size: 14px;"><span>{{ $qty }}</span></span>
                            <button type="button" @click="setQuantity({{ $id }}, {{ $qty + 1 }})" :disabled="busy" @disabled($atMax) aria-label="{{ __('store.product.increase') }}" style="width: 28px; height: 30px; border: none; background: transparent; cursor: pointer; font-size: 15px;{{ $atMax ? ' opacity: 0.35; cursor: default;' : '' }}">+</button>
                        </div>
                        <button type="button" @click="remove({{ $id }})" :disabled="busy" style="border: none; background: transparent; color: rgb(139, 135, 127); font-size: 12px; text-decoration: underline; cursor: pointer;">{{ __('store.theme.remove') }}</button>
                    </div>
                </div>
                <div style="font-size: 14px; color: rgb(32, 32, 32); white-space: nowrap;"><x-store.fv-price :money="$line->lineTotal" style="display: inline-flex; align-items: center;" /></div>
            </div>
        @endforeach
    </div>
    <div style="border-top: 1px solid rgb(236, 236, 236); padding: 22px 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
            <span style="font-size: 15px; color: rgb(102, 102, 102);">{{ __('store.theme.subtotal') }}</span>
            <span style="font-size: 18px; font-weight: 600; color: rgb(32, 32, 32);"><x-store.fv-price :money="$summary->subtotal" style="display: inline-flex; align-items: center;" /></span>
        </div>
        <p style="margin: 0px 0px 18px; font-size: 12px; color: rgb(168, 164, 158);">Shipping &amp; VAT calculated at checkout.</p>
        <a href="{{ store_route('checkout') }}" style="width: 100%; height: 52px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">{{ __('store.theme.checkout') }}</a>
        <a href="{{ store_route('cart') }}" style="width: 100%; height: 50px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">{{ __('store.theme.view_cart') }}</a>
    </div>
@endif
