{{-- Home page section: ribbon (order and visibility: Admin → Online Store → Customize) --}}
<div class="overflow-hidden bg-ink py-3.5 text-paper" aria-label="{{ __('store.home.promises_label') }}">
    <div class="home-marquee flex w-max gap-10 text-sm font-medium whitespace-nowrap">
        @foreach (range(1, 2) as $copy)
            <ul class="flex gap-10" @if ($copy === 2) aria-hidden="true" @endif>
                @foreach (['marquee_1', 'marquee_2', 'marquee_3', 'marquee_4', 'marquee_5'] as $key)
                    <li class="inline-flex items-center gap-10">{{ __("store.home.{$key}") }} <span class="text-brand-red" aria-hidden="true">◆</span></li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>
