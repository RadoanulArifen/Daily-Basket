{{-- Discount code box, in the design's style. The real forms live in
     discount-forms-outer, so this can sit inside another form (the checkout page). --}}
@php $formId ??= 'discount-form'; @endphp
<div style="border-top: 1px solid rgb(236, 236, 236); padding-top: 20px;">
    @if ($summary->discount)
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 15px;">
            <span style="display: inline-flex; align-items: center; gap: 8px; background: rgb(245, 244, 240); padding: 5px 10px; font-weight: 600; color: rgb(32, 32, 32);">{{ $summary->discount->code }}</span>
            <button form="{{ $formId }}-remove" style="border: none; background: transparent; color: rgb(102, 102, 102); font-size: 13px; text-decoration: underline; cursor: pointer;">{{ __('store.discount.remove') }}</button>
        </div>
    @else
        <label for="{{ $formId }}-code" style="display: block; font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 10px;">{{ __('store.discount.label') }}</label>
        <div style="display: flex; gap: 0px;">
            <input id="{{ $formId }}-code" form="{{ $formId }}" name="code" maxlength="40" autocomplete="off" value="{{ old('code') }}" dir="ltr"
                   style="flex: 1 1 0%; min-width: 0px; height: 44px; border: 1px solid rgb(217, 213, 207); border-right: none; background: rgb(255, 255, 255); padding: 0px 14px; font-size: 14px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(32, 32, 32); outline: none;">
            <button form="{{ $formId }}" style="flex-shrink: 0; height: 44px; padding: 0px 18px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.discount.apply') }}</button>
        </div>
        @error('code')<p role="alert" style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
    @endif
    @error('discount')<p role="alert" style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
</div>
