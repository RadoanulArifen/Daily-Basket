@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.enquiries.title'))

@section('content')
    @php
        $tabs = collect(\App\Http\Controllers\Admin\Sales\EnquiryController::STATUSES)->map(fn ($s) => [
            'label' => __("admin.enquiries.statuses.{$s}"),
            'url' => route('admin.enquiries.index', array_filter(['status' => $s, 'q' => $term ?: null])),
            'active' => $status === $s,
            'count' => $counts[$s] ?? null,
        ])->all();
    @endphp

    <x-admin.page-header :title="__('admin.enquiries.title')" :subtitle="__('admin.enquiries.subtitle')" />

    <x-admin.card :padding="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-2 sm:px-3">
            <x-admin.tabs :tabs="$tabs" />
            <form method="GET" class="p-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input name="q" value="{{ $term }}" placeholder="{{ __('admin.enquiries.search') }}" class="admin-field w-56 py-1.5 text-[13px]">
            </form>
        </div>

        @if ($enquiries->isEmpty())
            <x-admin.empty-state icon="mail" :title="__('admin.enquiries.empty_title')" :text="__('admin.enquiries.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($enquiries as $enquiry)
                    <li>
                        <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="flex flex-wrap items-center gap-x-5 gap-y-1 px-4 py-3.5 hover:bg-subdued sm:px-5">
                            <span class="min-w-40 flex-1">
                                <span class="block text-[14px] font-medium">{{ $enquiry->name }}</span>
                                <span class="block text-[13px] text-muted">{{ $enquiry->email }}@if ($enquiry->phone) · <span dir="ltr">{{ $enquiry->phone }}</span>@endif</span>
                            </span>
                            <span class="min-w-40 flex-1 text-[13px] text-ink-soft">
                                @if ($enquiry->product)<span class="block">{{ $enquiry->product->name }}</span>@endif
                                <span class="block truncate">{{ \Illuminate\Support\Str::limit($enquiry->message ?: $enquiry->category, 70) }}</span>
                            </span>
                            <span class="text-[13px] text-muted">{{ $enquiry->created_at->diffForHumans() }}</span>
                            <x-admin.badge :tone="$enquiry->status === 'new' ? 'attention' : ($enquiry->status === 'won' ? 'success' : 'default')">{{ __("admin.enquiries.statuses.{$enquiry->status}") }}</x-admin.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3">{{ $enquiries->links() }}</div>
        @endif
    </x-admin.card>
@endsection
