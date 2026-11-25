@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.reviews.title'))

@section('content')
    @php
        $tabs = collect(['pending', 'approved', 'rejected'])->map(fn ($s) => [
            'label' => __("admin.reviews.status.{$s}"), 'url' => route('admin.reviews.index', ['status' => $s]),
            'active' => $status === $s, 'count' => $s === 'pending' ? $pendingCount : null,
        ])->all();
    @endphp

    <x-admin.page-header :title="__('admin.reviews.title')" :subtitle="__('admin.reviews.subtitle')" />

    <x-admin.card :padding="false">
        <div class="border-b border-line px-2 sm:px-3"><x-admin.tabs :tabs="$tabs" /></div>
        @if ($reviews->isEmpty())
            <x-admin.empty-state icon="star" :title="__('admin.reviews.empty_title')" :text="__('admin.reviews.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($reviews as $review)
                    <li class="px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm">
                                    <span class="text-amber-500" aria-label="{{ __('admin.reviews.stars', ['count' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<span class="text-line-strong">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                    @if ($review->title)<span class="ms-2 font-medium">{{ $review->title }}</span>@endif
                                </p>
                                @if ($review->photos->isNotEmpty())
                                    <div class="mt-2 flex gap-2">
                                        @foreach ($review->photos as $photo)
                                            <a href="{{ $photo->url('large') }}" target="_blank" rel="noopener"><img src="{{ $photo->url('thumb') }}" alt="" class="size-16 rounded-lg border border-line object-cover"></a>
                                        @endforeach
                                    </div>
                                @endif
                                <p class="mt-1 whitespace-pre-line text-sm text-ink-soft" @if ($review->locale === 'ar') dir="rtl" lang="ar" @endif>{{ $review->body }}</p>
                                <p class="mt-2 text-xs text-muted">
                                    {{ $review->name }} · <a href="{{ route('admin.products.edit', $review->product_id) }}" class="hover:underline">{{ $review->product?->name }}</a>
                                    · {{ $review->created_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y') }}
                                    @if ($review->verified_purchase) · <span class="text-success-600">{{ __('admin.reviews.verified') }}</span>@endif
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1.5">
                                @foreach (['approved' => 'approve', 'rejected' => 'reject'] as $to => $label)
                                    @if ($review->status !== $to)
                                        <form method="POST" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('PUT')
                                            <input type="hidden" name="status" value="{{ $to }}">
                                            <x-admin.button size="sm" :variant="$to === 'approved' ? 'primary' : 'secondary'">{{ __("admin.reviews.{$label}") }}</x-admin.button>
                                        </form>
                                    @endif
                                @endforeach
                                <x-admin.confirm-delete icon-only :action="route('admin.reviews.destroy', $review)" :title="__('admin.reviews.delete_title')" :message="__('admin.reviews.delete_text')" />
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            {{ $reviews->links('components.admin.pagination') }}
        @endif
    </x-admin.card>
@endsection
