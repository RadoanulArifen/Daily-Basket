{{-- "Made to Order enquiry" popup — markup and styles from the design. Opened by the
     Custom Order buttons (window event 'open-custom-order'); those buttons still link to
     the full page, so the enquiry works without JavaScript too. --}}
@php
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $categories = \App\Models\Category::query()->active()->whereNull('parent_id')->orderBy('sort_order')->pluck('name');
@endphp

<div x-data="customOrder(@js(store_route('custom-order')))" @open-custom-order.window="show($event.detail)" @keydown.escape.window="close()">
<div x-show="open" x-cloak>
<div style="position: fixed; inset: 0px; z-index: 95; background: rgba(15, 15, 15, 0.6); backdrop-filter: blur(3px); display: flex; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 6vh 20px;"
     @click.self="close()" role="dialog" aria-modal="true" aria-labelledby="custom-order-title">
    <div x-ref="panel" tabindex="-1" @keydown.tab="trap($event)" style="position: relative; width: min(680px, 100%); background: rgb(255, 255, 255); padding: clamp(28px, 4vw, 48px); outline: none;">
        <button type="button" @click="close()" title="{{ __('store.common.close') }}" aria-label="{{ __('store.common.close') }}" style="position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; border: none; background: transparent; color: rgb(32, 32, 32); font-size: 22px; cursor: pointer; line-height: 1;">✕</button>

        <div x-show="! sent">
            <div style="font-size: 11px; letter-spacing: 0.24em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 10px;">{{ __('store.custom.title') }}</div>
            <h2 id="custom-order-title" style="margin: 0px 0px 10px; font-size: clamp(24px, 3vw, 32px); font-weight: 700; letter-spacing: -0.02em;">Made to Order enquiry</h2>
            <p style="margin: 0px 0px 26px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">Tell us how you'd like this piece. We'll quote and confirm by WhatsApp or email.</p>

            <form @submit.prevent="send($event)" class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                <input type="hidden" name="product" :value="product">
                <label style="{{ $label }}"><span>{{ __('store.custom.your_name') }}<x-store.required /></span>
                    <input name="name" required maxlength="120" autocomplete="name" style="{{ $input }}">
                </label>
                <label style="{{ $label }}"><span>{{ __('store.auth.email') }}<x-store.required /></span>
                    <input type="email" name="email" required maxlength="255" autocomplete="email" style="{{ $input }}">
                </label>
                <label style="{{ $label }}">{{ __('store.custom.phone_whatsapp') }}
                    <input name="phone" maxlength="40" autocomplete="tel" dir="ltr" style="{{ $input }}">
                </label>
                <label style="{{ $label }}">{{ __('store.custom.category') }}
                    <select name="category" x-ref="category" style="{{ $input }}">
                        <option value="">{{ __('store.custom.category_any') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="{{ $label }}">{{ __('store.custom.dimensions') }}
                    <input name="dimensions" maxlength="120" placeholder="{{ __('store.custom.dimensions_hint') }}" style="{{ $input }}">
                </label>
                <label style="{{ $label }}">{{ __('store.custom.material') }}
                    <input name="material" maxlength="120" placeholder="{{ __('store.custom.material_hint') }}" style="{{ $input }}">
                </label>
                <label style="{{ $label }} grid-column: 1 / -1;">{{ __('store.custom.message') }}
                    <textarea name="message" rows="3" maxlength="3000" placeholder="{{ __('store.custom.message_hint') }}" style="{{ $input }} resize: vertical;"></textarea>
                </label>

                <p x-show="error" x-cloak x-text="error" role="alert" style="grid-column: 1 / -1; margin: 0px; font-size: 14px; color: rgb(199, 64, 52);"></p>

                <div style="grid-column: 1 / -1;">
                    <button type="submit" :disabled="busy" style="height: 52px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.custom.submit') }}</button>
                </div>
            </form>
        </div>

        <div x-show="sent" x-cloak style="text-align: center; padding: 18px 0px;">
            <div aria-hidden="true" style="width: 48px; height: 48px; margin: 0px auto 18px; border-radius: 50%; background: rgb(76, 175, 125); color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 22px;">✓</div>
            <h2 style="margin: 0px 0px 10px; font-size: clamp(22px, 2.6vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.custom.sent_title') }}</h2>
            <p style="margin: 0px 0px 24px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">{{ __('store.custom.sent_text') }}</p>
            <button type="button" @click="close()" style="height: 50px; padding: 0px 30px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.common.close') }}</button>
        </div>
    </div>
</div>
</div>
</div>
