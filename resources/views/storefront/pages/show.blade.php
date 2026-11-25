@extends('storefront.layouts.app')

@section('title', $page->seo_title ?: $page->title)
@if (filled($page->seo_description))
    @section('meta_description', $page->seo_description)
@endif

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->breadcrumbs([['label' => $page->title]])) !!}</script>
@endpush

@section('content')
    <div class="mx-auto max-w-3xl px-4 pb-20 pt-8 sm:px-6">
        <x-store.breadcrumbs :items="[['label' => $page->title]]" />
        <h1 class="mt-6 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $page->title }}</h1>

        <div class="store-prose mt-8">{!! $page->html() !!}</div>

        @if ($page->show_contact_form)
            <section class="mt-12 border-t border-line pt-10" aria-labelledby="contact-heading">
                <h2 id="contact-heading" class="text-xl font-semibold">{{ __('store.contact.title') }}</h2>
                @if (session('contact_sent'))
                    <x-store.alert class="mt-6">{{ __('store.contact.sent') }}</x-store.alert>
                @else
                    <form method="POST" action="{{ store_route('pages.contact', ['slug' => $page->slug]) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                        @csrf
                        {{-- Honeypot: hidden from people, filled in by spam bots. --}}
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <x-store.input name="name" :label="__('store.checkout.full_name')" :value="old('name', auth('web')->user()?->name)" required autocomplete="name" />
                        <x-store.input name="email" type="email" :label="__('store.auth.email')" :value="old('email', auth('web')->user()?->email)" required autocomplete="email" />
                        <x-store.input name="phone" type="tel" :label="__('store.auth.phone')" :value="old('phone')" dir="ltr" class="sm:col-span-2" autocomplete="tel" />
                        <div class="space-y-2 sm:col-span-2">
                            <label for="message" class="block text-sm font-medium">{{ __('store.contact.message') }}</label>
                            <textarea id="message" name="message" rows="5" required maxlength="3000" class="store-field @error('message') store-field-error @enderror">{{ old('message') }}</textarea>
                            @error('message')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2"><x-store.button>{{ __('store.contact.send') }}</x-store.button></div>
                    </form>
                @endif
            </section>
        @endif
    </div>
@endsection
