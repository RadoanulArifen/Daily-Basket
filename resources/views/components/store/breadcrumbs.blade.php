{{-- <x-store.breadcrumbs :items="[['label' => 'Living', 'url' => ...], ['label' => 'Cushion']]" /> --}}
@props(['items'])

<nav aria-label="{{ __('store.nav.breadcrumbs') }}" {{ $attributes->class('text-sm text-stone') }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ store_route('home') }}" class="hover:text-ink">{{ __('store.nav.home') }}</a></li>
        @foreach ($items as $item)
            <li aria-hidden="true"><x-icon name="chevron-right" class="size-3.5 rtl:rotate-180" /></li>
            <li>
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-ink">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-ink-soft">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
