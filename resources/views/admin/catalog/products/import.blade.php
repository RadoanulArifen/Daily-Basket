@extends('admin.layouts.app')

@section('title', __('admin.import.title'))

@section('content')
    <x-admin.page-header :title="__('admin.import.title')" :back="route('admin.products.index')" :subtitle="__('admin.import.subtitle')" />

    @if ($errors->any())
        <x-admin.alert tone="danger" class="mb-6" :title="__('admin.import.problems')">
            <ul class="list-disc ps-5">@foreach (collect($errors->all())->take(50) as $message)<li>{{ $message }}</li>@endforeach</ul>
        </x-admin.alert>
    @endif

    @if ($report)
        <x-admin.card :title="__('admin.import.check_title')" class="mb-6">
            <dl class="grid grid-cols-3 gap-4 text-center">
                <div class="rounded-lg bg-canvas p-3"><dt class="text-[13px] text-muted">{{ __('admin.import.will_create') }}</dt><dd class="text-xl font-semibold">{{ $report['create'] }}</dd></div>
                <div class="rounded-lg bg-canvas p-3"><dt class="text-[13px] text-muted">{{ __('admin.import.will_update') }}</dt><dd class="text-xl font-semibold">{{ $report['update'] }}</dd></div>
                <div class="rounded-lg bg-canvas p-3"><dt class="text-[13px] text-muted">{{ __('admin.import.variants') }}</dt><dd class="text-xl font-semibold">{{ $report['variants'] }}</dd></div>
            </dl>

            @if ($report['ignored'])
                <p class="mt-4 text-[13px] text-muted">{{ __('admin.import.ignored', ['columns' => implode(', ', $report['ignored'])]) }}</p>
            @endif

            @if ($report['errors'])
                <x-admin.alert tone="danger" class="mt-4" :title="trans_choice('admin.import.error_count', count($report['errors']), ['count' => count($report['errors'])])">
                    <ul class="list-disc ps-5">@foreach (array_slice($report['errors'], 0, 50) as $message)<li>{{ $message }}</li>@endforeach</ul>
                    <p class="mt-2">{{ __('admin.import.fix_and_retry') }}</p>
                </x-admin.alert>
            @else
                <form method="POST" action="{{ route('admin.products.import.store') }}" class="mt-5 flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <x-admin.button icon="check">{{ __('admin.import.confirm') }}</x-admin.button>
                    <span class="text-[13px] text-muted">{{ __('admin.import.confirm_help') }}</span>
                </form>
            @endif
        </x-admin.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card :title="__('admin.import.upload_title')" class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.products.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600">
                <x-admin.button variant="secondary">{{ __('admin.import.check') }}</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.card :title="__('admin.import.how_title')">
            <ol class="list-decimal space-y-2 ps-5 text-[13px] text-ink-soft">
                <li>{!! __('admin.import.how_1', ['export' => '<a href="'.route('admin.products.export').'" class="font-medium text-brand-600 hover:underline">'.e(__('admin.import.export')).'</a>', 'template' => '<a href="'.route('admin.products.import.template').'" class="font-medium text-brand-600 hover:underline">'.e(__('admin.import.template')).'</a>']) !!}</li>
                <li>{{ __('admin.import.how_2') }}</li>
                <li>{{ __('admin.import.how_3') }}</li>
                <li>{{ __('admin.import.how_4') }}</li>
                <li>{{ __('admin.import.how_5') }}</li>
            </ol>
        </x-admin.card>
    </div>
@endsection
