{{-- Usage: {{ $items->links('components.admin.pagination') }} --}}
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-line bg-subdued px-3 py-2 text-[13px]" aria-label="{{ __('admin.common.pagination') }}">
        <p class="text-muted">
            {{ __('admin.common.showing', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex size-8 items-center justify-center rounded-lg text-line-strong" aria-hidden="true"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex size-8 items-center justify-center rounded-lg bg-surface shadow-button ring-1 ring-black/10 hover:bg-subdued" aria-label="{{ __('admin.common.previous') }}"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></a>
            @endif

            <span class="px-2 text-ink-soft">{{ __('admin.common.page_of', ['page' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex size-8 items-center justify-center rounded-lg bg-surface shadow-button ring-1 ring-black/10 hover:bg-subdued" aria-label="{{ __('admin.common.next') }}"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></a>
            @else
                <span class="inline-flex size-8 items-center justify-center rounded-lg text-line-strong" aria-hidden="true"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></span>
            @endif
        </div>
    </nav>
@endif
