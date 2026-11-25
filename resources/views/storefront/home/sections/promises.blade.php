{{-- Home page section: promises (order and visibility: Admin → Online Store → Customize) --}}
<section class="mx-auto max-w-shop px-4 py-20 sm:px-6 lg:px-10 lg:py-28" aria-label="{{ __('store.home.promises_label') }}">
    <ul class="grid gap-4 sm:gap-6 md:grid-cols-3">
        @foreach (['delivery' => 'truck', 'payment' => 'credit-card', 'returns' => 'rotate-ccw'] as $key => $icon)
            <li class="rounded-3xl bg-wool-soft p-6 sm:p-8">
                <span class="flex size-11 items-center justify-center rounded-full bg-paper text-brand-red"><x-icon :name="$icon" class="size-5" /></span>
                <p class="mt-5 text-lg font-semibold tracking-tight">{{ __("store.home.promise_{$key}_title") }}</p>
                <p class="mt-1.5 text-[15px] text-ink-soft">{{ __("store.home.promise_{$key}_text") }}</p>
            </li>
        @endforeach
    </ul>
</section>
