{{-- Usage: {{ $products->links('components.store.pagination') }} --}}
@if ($paginator->hasPages())
    <nav class="mt-14 flex items-center justify-center gap-2" aria-label="{{ __('store.common.pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="flex size-11 items-center justify-center rounded-md text-stone/40" aria-hidden="true"><x-icon name="chevron-left" class="size-5 rtl:rotate-180" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex size-11 items-center justify-center rounded-md border border-line hover:border-ink" aria-label="{{ __('store.common.previous') }}"><x-icon name="chevron-left" class="size-5 rtl:rotate-180" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-stone">…</span>
            @elseif (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="flex size-11 items-center justify-center rounded-md bg-ink text-[15px] text-paper">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="hidden size-11 items-center justify-center rounded-md text-[15px] hover:bg-wool-soft sm:flex">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex size-11 items-center justify-center rounded-md border border-line hover:border-ink" aria-label="{{ __('store.common.next') }}"><x-icon name="chevron-right" class="size-5 rtl:rotate-180" /></a>
        @else
            <span class="flex size-11 items-center justify-center rounded-md text-stone/40" aria-hidden="true"><x-icon name="chevron-right" class="size-5 rtl:rotate-180" /></span>
        @endif
    </nav>
@endif
