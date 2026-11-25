{{--
    Discount code box. $formId lets the inputs belong to a form placed elsewhere
    (the checkout page is itself one big form, and forms can't be nested).
--}}
@php $formId ??= 'discount-form'; @endphp
<div class="border-t border-line pt-5">
    @if ($summary->discount)
        <div class="flex items-center justify-between gap-3 text-[15px]">
            <span class="inline-flex items-center gap-2 rounded-sm bg-paper px-2.5 py-1 font-medium"><x-icon name="tag" class="size-4 text-brand-red" /> {{ $summary->discount->code }}</span>
            <button form="{{ $formId }}-remove" class="text-sm text-ink-soft underline underline-offset-4 hover:text-ink">{{ __('store.discount.remove') }}</button>
        </div>
    @else
        <label for="{{ $formId }}-code" class="text-[15px] font-medium">{{ __('store.discount.label') }}</label>
        <div class="mt-2 flex gap-2">
            <input id="{{ $formId }}-code" form="{{ $formId }}" name="code" maxlength="40" autocomplete="off" value="{{ old('code') }}"
                   class="store-field py-2.5! uppercase @error('code') store-field-error @enderror" dir="ltr">
            <button form="{{ $formId }}" class="shrink-0 rounded-md border border-ink px-4 text-[15px] font-medium hover:bg-ink hover:text-paper">{{ __('store.discount.apply') }}</button>
        </div>
        @error('code')<p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
    @endif
    @error('discount')<p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
</div>
