@extends('admin.layouts.app')

@section('title', $enquiry->name)

@section('content')
    <x-admin.page-header :title="$enquiry->name" :back="route('admin.enquiries.index')" :subtitle="$enquiry->created_at->translatedFormat('j F Y, H:i')">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="'mailto:'.$enquiry->email.'?subject='.rawurlencode(__('admin.enquiries.reply_subject'))" icon="mail">{{ __('admin.enquiries.reply') }}</x-admin.button>
            @if ($enquiry->phone)
                <x-admin.button variant="secondary" :href="'https://wa.me/'.preg_replace('/\D/', '', $enquiry->phone)" target="_blank">{{ __('admin.enquiries.whatsapp') }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-admin.card class="lg:col-span-2">
            <h2 class="text-[15px] font-semibold">{{ __('admin.enquiries.what_they_asked') }}</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    __('store.custom.piece') => $enquiry->product?->name,
                    __('store.custom.category') => $enquiry->category,
                    __('store.custom.dimensions') => $enquiry->dimensions,
                    __('store.custom.material') => $enquiry->material,
                    __('store.custom.finish') => $enquiry->finish,
                    __('store.custom.quantity') => $enquiry->quantity > 1 ? $enquiry->quantity : null,
                    __('store.custom.budget') => $enquiry->budget,
                    __('store.custom.timeline') => $enquiry->timeline,
                ] as $label => $value)
                    @continue (blank($value))
                    <div>
                        <dt class="text-[13px] text-muted">{{ $label }}</dt>
                        <dd class="text-[14px]">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($enquiry->message)
                <div class="mt-5 border-t border-line pt-4">
                    <div class="text-[13px] text-muted">{{ __('store.contact.message') }}</div>
                    <p class="mt-1 whitespace-pre-line text-[14px] leading-relaxed">{{ $enquiry->message }}</p>
                </div>
            @endif
        </x-admin.card>

        <x-admin.card>
            <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="status" class="mb-1 block text-[13px] font-medium">{{ __('admin.enquiries.status') }}</label>
                    <select id="status" name="status" class="admin-field">
                        @foreach (\App\Http\Controllers\Admin\Sales\EnquiryController::STATUSES as $value)
                            <option value="{{ $value }}" @selected($enquiry->status === $value)>{{ __("admin.enquiries.statuses.{$value}") }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="admin_note" class="mb-1 block text-[13px] font-medium">{{ __('admin.enquiries.note') }}</label>
                    <textarea id="admin_note" name="admin_note" rows="5" maxlength="2000" class="admin-field" placeholder="{{ __('admin.enquiries.note_help') }}">{{ old('admin_note', $enquiry->admin_note) }}</textarea>
                </div>

                <x-admin.button>{{ __('admin.common.save') }}</x-admin.button>
            </form>

            <dl class="mt-6 space-y-2 border-t border-line pt-4 text-[13px]">
                <div class="flex justify-between gap-3"><dt class="text-muted">{{ __('store.auth.email') }}</dt><dd class="text-end"><a href="mailto:{{ $enquiry->email }}" class="underline">{{ $enquiry->email }}</a></dd></div>
                @if ($enquiry->phone)<div class="flex justify-between gap-3"><dt class="text-muted">{{ __('store.auth.phone') }}</dt><dd dir="ltr">{{ $enquiry->phone }}</dd></div>@endif
                @if ($enquiry->user_id)<div class="flex justify-between gap-3"><dt class="text-muted">{{ __('admin.enquiries.customer') }}</dt><dd>{{ __('admin.enquiries.has_account') }}</dd></div>@endif
            </dl>
        </x-admin.card>
    </div>
@endsection
