{{-- Home page section: promos (order and visibility: Admin → Online Store → Customize) --}}
@if ($promoBanners->isNotEmpty())
    <section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28">
        <ul @class(['grid gap-4 sm:gap-6', 'md:grid-cols-2' => $promoBanners->count() === 2, 'md:grid-cols-3' => $promoBanners->count() === 3])>
            @foreach ($promoBanners as $banner)
                <li>
                    @php $href = $banner->href(); $tileClass = 'group relative flex min-h-72 flex-col justify-end overflow-hidden rounded-3xl bg-date p-7 text-paper'; @endphp
                    @if ($href)<a href="{{ $href }}" class="{{ $tileClass }}">@else<div class="{{ $tileClass }}">@endif
                        @if ($banner->image)<img src="{{ $banner->image->url('medium') }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover opacity-70 transition duration-700 group-hover:scale-105">@endif
                        <span class="relative">
                            <span class="block text-2xl font-semibold tracking-tight">{{ $banner->title }}</span>
                            @if ($banner->text)<span class="mt-1 block text-[15px] text-paper/85">{{ $banner->text }}</span>@endif
                            @if ($banner->button_label)<span class="mt-4 inline-flex h-10 items-center rounded-full bg-paper px-5 text-sm font-medium text-ink">{{ $banner->button_label }}</span>@endif
                        </span>
                    @if ($href)</a>@else</div>@endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
