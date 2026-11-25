@extends('storefront.layouts.app')

{{-- Custom Order — the design has no such page (its header button does nothing), so this
     is built in the design's own type, spacing and controls. --}}
@section('furnivo', true)
@section('title', __('store.custom.title'))
@section('meta_description', __('store.custom.intro'))

@php
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $errorText = 'margin: 6px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);';
    $sent = session('custom_sent') || request()->boolean('sent');
@endphp

@section('content')
    <section style="width: 100%; padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.custom.title') }}</span>
        </nav>

        @if ($sent)
            <div style="max-width: 760px; margin: 0px auto; text-align: center; padding: clamp(40px, 6vw, 80px) 0px;">
                <div aria-hidden="true" style="width: 48px; height: 48px; margin: 0px auto 20px; border-radius: 50%; background: rgb(76, 175, 125); color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 22px;">✓</div>
                <h1 style="margin: 0px 0px 14px; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; letter-spacing: -0.02em;">{{ __('store.custom.sent_title') }}</h1>
                <p style="margin: 0px auto; max-width: 48ch; font-size: 17px; line-height: 29px; color: rgb(85, 85, 85);">{{ __('store.custom.sent_text') }}</p>
                <a href="{{ store_route('shop') }}" class="scpc" style="display: inline-flex; align-items: center; justify-content: center; height: 54px; padding: 0px 34px; margin-top: 30px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; transition: background 250ms, color 250ms;">{{ __('store.cart.continue') }}</a>
            </div>
        @else
            <div class="cart-cols fx-cart-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: clamp(30px, 5vw, 64px); align-items: start;">
                <div style="min-width: 0px;">
                    <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 14px;"><span>{{ __('store.custom.eyebrow') }}</span></div>
                    <h1 style="margin: 0px 0px 16px; font-size: clamp(32px, 4.2vw, 56px); line-height: 1.04; font-weight: 800; letter-spacing: -0.03em;">{{ __('store.custom.title') }}</h1>
                    <p style="margin: 0px 0px 34px; max-width: 58ch; font-size: 17px; line-height: 29px; color: rgb(85, 85, 85);">{{ __('store.custom.intro') }}</p>

                    @if ($errors->any())
                        <p role="alert" style="margin: 0px 0px 22px; padding: 14px 18px; border: 1px solid rgb(199, 64, 52); color: rgb(199, 64, 52); font-size: 14px;">{{ __('store.checkout.fix_errors') }}</p>
                    @endif

                    <form method="POST" action="{{ store_route('custom-order.store') }}" novalidate>
                        @csrf
                        @if ($product)<input type="hidden" name="product" value="{{ $product->slug }}">@endif

                        <div class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                            <label style="{{ $label }}">{{ __('store.checkout.full_name') }}
                                <input name="name" value="{{ old('name', auth('web')->user()?->name) }}" autocomplete="name" required style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.auth.email') }}
                                <input type="email" name="email" value="{{ old('email', auth('web')->user()?->email) }}" autocomplete="email" required style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.auth.phone') }}
                                <input name="phone" value="{{ old('phone', auth('web')->user()?->phone) }}" autocomplete="tel" dir="ltr" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.category') }}
                                <select name="category" style="{{ $input }}">
                                    <option value="">{{ __('store.custom.category_any') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->name }}" @selected(old('category', $product?->categories->first()?->parent?->name ?? $product?->categories->first()?->name) === $category->name)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.dimensions') }}
                                <input name="dimensions" value="{{ old('dimensions') }}" placeholder="{{ __('store.custom.dimensions_hint') }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.quantity') }}
                                <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity', 1) }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.material') }}
                                <input name="material" value="{{ old('material') }}" placeholder="{{ __('store.custom.material_hint') }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.finish') }}
                                <input name="finish" value="{{ old('finish') }}" placeholder="{{ __('store.custom.finish_hint') }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.budget') }}
                                <input name="budget" value="{{ old('budget') }}" placeholder="{{ __('store.custom.budget_hint') }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}">{{ __('store.custom.timeline') }}
                                <input name="timeline" value="{{ old('timeline') }}" placeholder="{{ __('store.custom.timeline_hint') }}" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.custom.message') }}<x-store.required /></span>
                                <textarea name="message" rows="5" required minlength="10" maxlength="3000" placeholder="{{ __('store.custom.message_hint') }}" style="{{ $input }} resize: vertical;">{{ old('message') }}</textarea>
                            </label>
                            @foreach (['name', 'email', 'phone', 'message', 'quantity'] as $field)
                                @error($field)<p style="{{ $errorText }} grid-column: 1 / -1;">{{ $message }}</p>@enderror
                            @endforeach
                        </div>

                        <button type="submit" class="scpb" style="margin-top: 26px; height: 54px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; transition: opacity 250ms;">{{ __('store.custom.submit') }}</button>
                    </form>
                </div>

                <aside style="min-width: 0px; border: 1px solid rgb(236, 236, 236); padding: 26px;">
                    @if ($product)
                        <div style="font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 14px;">{{ __('store.custom.based_on') }}</div>
                        <a href="{{ store_route('products.show', ['slug' => $product->slug]) }}" style="display: flex; gap: 12px; align-items: center; margin-bottom: 22px;">
                            @php $media = $product->media->first(); @endphp
                            <div style="width: 64px; height: 64px; overflow: hidden; background: rgb(239, 236, 231); flex-shrink: 0;">
                                @if ($media)<img src="{{ $media->url('thumb') }}" alt="" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                            </div>
                            <div style="min-width: 0px;">
                                @if ($product->brandRecord)<div style="font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(139, 135, 127);">{{ $product->brandRecord->name }}</div>@endif
                                <div style="font-size: 15px; font-weight: 600; color: rgb(32, 32, 32);">{{ $product->name }}</div>
                                <div style="font-size: 13px; color: rgb(102, 102, 102);">{{ \App\Support\RichText::text($product->short_description) }}</div>
                            </div>
                        </a>
                    @endif

                    <div style="font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 14px;">{{ __('store.custom.how_title') }}</div>
                    <ol style="margin: 0px; padding: 0px 0px 0px 18px; font-size: 14px; line-height: 23px; color: rgb(102, 102, 102);">
                        @foreach (__('store.custom.steps') as $step)
                            <li style="margin-bottom: 10px;">{{ $step }}</li>
                        @endforeach
                    </ol>
                </aside>
            </div>
        @endif
    </section>
@endsection
