@extends('admin.settings.layout')

@section('title', __('admin.activity.title'))

@section('settings')
    @php $tz = config('store.display_timezone'); @endphp
    <x-admin.page-header :title="__('admin.activity.title')" :back="route('admin.settings.index')" :subtitle="__('admin.activity.subtitle', ['days' => \App\Models\AuditLog::KEEP_DAYS])" />

    <x-admin.card :padding="false">
        <form method="GET" class="flex flex-col gap-2 border-b border-line p-3 sm:flex-row sm:px-4">
            <label class="relative flex-1">
                <span class="sr-only">{{ __('admin.activity.search') }}</span>
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('admin.activity.search') }}" class="admin-field ps-9">
            </label>
            <select name="type" x-data @change="$el.form.submit()" class="admin-field w-auto" aria-label="{{ __('admin.activity.type') }}">
                <option value="">{{ __('admin.activity.all_types') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ __("admin.activity.types.{$type}") !== "admin.activity.types.{$type}" ? __("admin.activity.types.{$type}") : $type }}</option>
                @endforeach
            </select>
            <select name="admin" x-data @change="$el.form.submit()" class="admin-field w-auto" aria-label="{{ __('admin.activity.by') }}">
                <option value="">{{ __('admin.activity.everyone') }}</option>
                @foreach ($admins as $id => $name)<option value="{{ $id }}" @selected((string) ($filters['admin'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </form>

        @if ($logs->isEmpty())
            <x-admin.empty-state icon="clipboard-list" :title="__('admin.activity.empty_title')" :text="__('admin.activity.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($logs as $log)
                    <li x-data="{ open: false }" class="px-4 py-3 sm:px-5">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <x-admin.badge :tone="match ($log->action) { 'created' => 'success', 'deleted' => 'danger', default => 'info' }">{{ __("admin.activity.actions.{$log->action}") }}</x-admin.badge>
                            <span class="text-muted">{{ __("admin.activity.types.{$log->subject_type}") !== "admin.activity.types.{$log->subject_type}" ? __("admin.activity.types.{$log->subject_type}") : $log->subject_type }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium">{{ $log->subject_label ?? '#'.$log->subject_id }}</span>
                            <span class="text-[13px] text-muted">{{ $log->admin?->name ?? '—' }} · {{ $log->created_at->timezone($tz)->translatedFormat('j M Y, H:i') }}</span>
                            @if ($log->changes)
                                <button type="button" @click="open = ! open" :aria-expanded="open" class="text-[13px] text-brand-600 hover:underline">{{ trans_choice('admin.activity.fields', count($log->changes), ['count' => count($log->changes)]) }}</button>
                            @endif
                        </div>
                        @if ($log->changes)
                            <div x-show="open" x-cloak x-collapse class="mt-3 overflow-x-auto rounded-lg border border-line">
                                <table class="w-full text-[13px]">
                                    <tbody class="divide-y divide-line">
                                        @foreach ($log->changes as $field => [$old, $new])
                                            <tr>
                                                <th class="w-40 bg-canvas/60 px-3 py-2 text-start font-medium">{{ $field }}</th>
                                                @if ($log->action === 'updated')<td class="px-3 py-2 text-danger-600 line-through decoration-danger-600/40" dir="auto">{{ is_array($old) ? json_encode($old, JSON_UNESCAPED_UNICODE) : var_export($old, true) }}</td>@endif
                                                <td class="px-3 py-2 text-ink" dir="auto">{{ is_array($new) ? json_encode($new, JSON_UNESCAPED_UNICODE) : var_export($new, true) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            {{ $logs->links('components.admin.pagination') }}
        @endif
    </x-admin.card>
@endsection
