@extends('admin.layouts.app')

@section('title', __('admin.draft.drafts_title'))

@section('content')
    <x-admin.page-header :title="__('admin.draft.drafts_title')" :subtitle="__('admin.draft.drafts_subtitle')" :back="route('admin.orders.index')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.draft-orders.create')" icon="plus">{{ __('admin.draft.title') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false" class="mt-6">
        @if ($drafts->isEmpty())
            <x-admin.empty-state icon="file-text" :title="__('admin.draft.drafts_empty_title')" :text="__('admin.draft.drafts_empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($drafts as $draft)
                    <li class="flex flex-wrap items-center gap-x-5 gap-y-1 px-4 py-3.5 sm:px-5">
                        <a href="{{ route('admin.draft-orders.create', ['draft' => $draft->id]) }}" class="min-w-48 flex-1">
                            <span class="block text-[14px] font-medium">{{ $draft->label() }}</span>
                            <span class="block text-[13px] text-muted">
                                {{ trans_choice('admin.draft.items', $draft->items_count, ['count' => $draft->items_count]) }}
                                @if ($draft->subtotal > 0) · {{ price_admin(\App\Support\Money::ofMinor($draft->subtotal, config('store.base_currency'))) }}@endif
                            </span>
                        </a>
                        <span class="text-[13px] text-muted">{{ __('admin.draft.saved_by', ['name' => $draft->admin?->name ?: '—']) }}</span>
                        <span class="text-[13px] text-muted">{{ $draft->updated_at->diffForHumans() }}</span>
                        <x-admin.button variant="secondary" size="sm" :href="route('admin.draft-orders.create', ['draft' => $draft->id])">{{ __('admin.draft.resume') }}</x-admin.button>
                        <form method="POST" action="{{ route('admin.draft-orders.destroy-draft', $draft) }}" x-data
                              @submit="if (! confirm(@js(__('admin.draft.delete_confirm')))) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button class="text-[13px] text-muted underline">{{ __('admin.common.delete') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3">{{ $drafts->links() }}</div>
        @endif
    </x-admin.card>
@endsection
