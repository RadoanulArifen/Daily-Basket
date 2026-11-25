@php
    $shopMenu = $storeMenus->get('footer-shop')?->tree ?? collect();
    $helpMenu = $storeMenus->get('footer-help')?->tree ?? collect();
    $socials = collect([
        'Instagram' => setting('general.instagram_url'),
        'Facebook' => setting('general.facebook_url'),
        'TikTok' => setting('general.tiktok_url'),
        'X' => setting('general.x_url'),
    ])->filter();
    $email = setting('general.store_email');
    $phone = setting('general.store_phone');
    $whatsapp = setting('general.whatsapp');
    $trn = setting('general.trn');
@endphp

<footer class="mt-16 bg-wool-soft text-ink sm:mt-24">
    <div class="weave-band" aria-hidden="true"></div>

    <div class="mx-auto grid max-w-shop gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:px-10 lg:py-16">
        <div class="lg:col-span-5">
            <p class="text-xl font-semibold tracking-tight">{{ $storeName }}</p>
            @if ($tagline = settings()->trans('general.tagline'))
                <p class="mt-3 max-w-sm text-[15px] leading-relaxed text-ink-soft">{{ $tagline }}</p>
            @endif

            <ul class="mt-6 space-y-2 text-[15px] text-ink-soft">
                @if ($email)
                    <li><a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 hover:text-ink"><x-icon name="mail" class="size-4" /> {{ $email }}</a></li>
                @endif
                @if ($phone)
                    <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="inline-flex items-center gap-2 hover:text-ink"><x-icon name="phone" class="size-4" /> <span dir="ltr">{{ $phone }}</span></a></li>
                @endif
                @if ($whatsapp)
                    <li><a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 hover:text-ink"><x-icon name="message-circle" class="size-4" /> WhatsApp</a></li>
                @endif
            </ul>
        </div>

        @foreach ([[__('store.footer.shop'), $shopMenu], [__('store.footer.help'), $helpMenu]] as [$heading, $items])
            @if ($items->isNotEmpty())
                <nav class="lg:col-span-2" aria-label="{{ $heading }}">
                    <h2 class="text-sm font-semibold">{{ $heading }}</h2>
                    <ul class="mt-4 space-y-2.5 text-[15px] text-ink-soft">
                        @foreach ($items as $item)
                            <li><a href="{{ $item->href() }}" class="hover:text-ink">{{ $item->label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        @endforeach

        @if ($address = settings()->trans('general.address'))
            <div class="lg:col-span-3">
                <h2 class="text-sm font-semibold">{{ __('store.footer.visit') }}</h2>
                <address class="mt-4 text-[15px] not-italic leading-relaxed whitespace-pre-line text-ink-soft">{{ $address }}</address>
                @if ($socials->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                        @foreach ($socials as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener" class="underline decoration-ink/25 underline-offset-4 hover:decoration-ink">{{ $network }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>

    <div class="border-t border-line">
        <div class="mx-auto flex max-w-shop flex-col gap-3 px-4 py-6 text-[13px] text-stone sm:px-6 md:flex-row md:items-center md:justify-between lg:px-10">
            <p>
                © {{ now()->year }} {{ $storeName }}.
                @if ($trn){{ __('store.footer.trn') }} <span dir="ltr">{{ $trn }}</span>@endif
            </p>
            <p>{{ __('store.footer.payments') }}</p>
        </div>
    </div>
</footer>
