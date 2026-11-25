@extends('admin.settings.layout')

@section('title', __('admin_backup.title'))

{{--
    Settings → Backup & clean-up (store owner only).
    $backups: list of App\Services\Maintenance\BackupFile
    $options: ['auto' => bool, 'keep' => int, 'schedule' => off|daily|weekly|monthly, 'items' => list<string>]
    $counts:  item key => how many rows or folders would go right now
    $lastRun: ['at' => Carbon, 'summary' => string, 'by' => string]|null
--}}
@section('settings')
    <x-admin.page-header :title="__('admin_backup.title')" :back="route('admin.settings.index')" :subtitle="__('admin_backup.subtitle')" />

    @if (session('backup_result'))
        <x-admin.alert :tone="session('backup_result.ok') ? 'success' : 'danger'" class="mt-6">{{ session('backup_result.message') }}</x-admin.alert>
    @endif

    {{-- Backups --}}
    <x-admin.form-section id="backups" :title="__('admin_backup.backups_title')" :description="__('admin_backup.backups_text')" class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-[13px]">
                @if ($backups->isNotEmpty())
                    <p class="font-medium text-ink-strong">{{ __('admin_backup.last_backup', ['when' => $backups->first()->date->timezone(config('store.display_timezone'))->format('j M Y, H:i')]) }}</p>
                    <p class="text-muted">{{ trans_choice('admin_backup.kept_count', $backups->count(), ['count' => $backups->count()]) }} · {{ \Illuminate\Support\Number::fileSize($backups->sum('size'), 1) }}</p>
                @else
                    <p class="text-muted">{{ __('admin_backup.none_yet') }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.settings.backup.store') }}">
                @csrf
                <x-admin.button icon="database">{{ __('admin_backup.backup_now') }}</x-admin.button>
            </form>
        </div>

        @if ($backups->isNotEmpty())
            <div class="-mx-4 border-t border-line">
                <table class="w-full text-[13px]">
                    <thead class="hidden text-start text-xs text-muted sm:table-header-group">
                        <tr class="border-b border-line">
                            <th class="px-4 py-2 text-start font-medium">{{ __('admin_backup.col_date') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('admin_backup.col_by') }}</th>
                            <th class="px-4 py-2 text-end font-medium">{{ __('admin_backup.col_size') }}</th>
                            <th class="px-4 py-2"><span class="sr-only">{{ __('admin_backup.col_actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr class="border-b border-line last:border-0" data-backup="{{ $backup->name }}">
                                <td class="px-4 pt-3 sm:py-3">
                                    <span class="block font-medium whitespace-nowrap text-ink-strong">{{ $backup->date->timezone(config('store.display_timezone'))->format('j M Y, H:i') }}</span>
                                    <span class="block text-xs text-muted sm:hidden">{{ $backup->by }} · {{ \Illuminate\Support\Number::fileSize($backup->size, 1) }}</span>
                                    <span class="hidden font-mono text-[11px] text-muted sm:block" dir="ltr">{{ $backup->name }}</span>
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">
                                    @if ($backup->auto)
                                        <x-admin.badge>{{ __('admin_backup.automatic') }}</x-admin.badge>
                                    @else
                                        {{ $backup->by }}
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-end whitespace-nowrap sm:table-cell">{{ \Illuminate\Support\Number::fileSize($backup->size, 1) }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <x-admin.button variant="secondary" size="sm" icon="download" :href="route('admin.settings.backup.download', $backup->name)">{{ __('admin_backup.download') }}</x-admin.button>
                                        <x-admin.confirm-delete icon-only :action="route('admin.settings.backup.destroy', $backup->name)"
                                            :title="__('admin_backup.delete_title')" :message="__('admin_backup.delete_text', ['file' => $backup->name])" :label="__('admin_backup.delete')" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-admin.alert>{{ __('admin_backup.restore_note') }}</x-admin.alert>
    </x-admin.form-section>

    {{-- Automatic backups --}}
    <x-admin.form-section :title="__('admin_backup.auto_title')" :description="__('admin_backup.auto_text')" class="mt-8">
        <form method="POST" action="{{ route('admin.settings.backup.update') }}" class="space-y-4" x-data="{ dirty: false }" @change="dirty = true">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="backups">
            <x-admin.toggle name="auto" :label="__('admin_backup.auto_label')" :description="__('admin_backup.auto_help')" :checked="$options['auto']" />
            <div class="max-w-xs">
                <x-admin.select name="keep" :label="__('admin_backup.keep_label')" :value="$options['keep']"
                    :options="collect([3, 7, 14, 30])->mapWithKeys(fn ($n) => [$n => trans_choice('admin_backup.keep_option', $n, ['count' => $n])])->all()"
                    :help="__('admin_backup.keep_help')" />
            </div>
            <x-admin.button x-bind:disabled="! dirty">{{ __('admin.common.save') }}</x-admin.button>
        </form>
    </x-admin.form-section>

    {{-- Clean-up --}}
    <x-admin.form-section id="cleanup" :title="__('admin_backup.cleanup_title')" :description="__('admin_backup.cleanup_text')" class="mt-8">
        <form method="POST" action="{{ route('admin.settings.backup.cleanup') }}" x-data="{ confirming: false, ticked: {{ count($options['items']) }} }"
              @change="ticked = $el.querySelectorAll('input[name=\'items[]\']:checked').length" class="space-y-3">
            @csrf
            <ul class="divide-y divide-line rounded-lg border border-line">
                @foreach (\App\Services\Maintenance\Cleanup::ITEMS as $item)
                    <li>
                        <label class="flex cursor-pointer items-start gap-3 px-3 py-2.5">
                            <input type="checkbox" name="items[]" value="{{ $item }}" class="mt-0.5 size-4 rounded border-line-strong accent-brand-600" @checked(in_array($item, $options['items'], true))>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-medium text-ink-strong">{{ __('admin_backup.items.'.$item) }}</span>
                                <span class="block text-xs text-muted">{{ __('admin_backup.items_help.'.$item) }}</span>
                            </span>
                            <span @class(['shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold', 'bg-subdued text-muted' => ! ($counts[$item] ?? 0), 'bg-warning-50 text-warning-700' => ($counts[$item] ?? 0) > 0])
                                  data-count="{{ $item }}">{{ trans_choice('admin_backup.units.'.$item, $counts[$item] ?? 0, ['count' => number_format($counts[$item] ?? 0)]) }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>

            <div class="flex flex-wrap items-center gap-3">
                <x-admin.button type="button" icon="brush-cleaning" x-on:click="confirming = true" x-bind:disabled="ticked === 0">{{ __('admin_backup.clean_now') }}</x-admin.button>
                <p class="text-xs text-muted">{{ __('admin_backup.clean_backup_first') }}</p>
            </div>

            {{-- Confirm first: what goes, and that a backup is made before anything is removed. --}}
            <div x-show="confirming" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 p-3 sm:items-center" @keydown.escape.window="confirming = false">
                <div class="w-full max-w-md rounded-2xl bg-surface p-5 shadow-popover" role="dialog" aria-modal="true" aria-labelledby="cleanup-title" @click.outside="confirming = false">
                    <h3 id="cleanup-title" class="text-base font-semibold text-ink-strong">{{ __('admin_backup.confirm_title') }}</h3>
                    <p class="mt-2 text-[13px] text-ink-soft">{{ __('admin_backup.confirm_text') }}</p>
                    <div class="mt-5 flex justify-end gap-2">
                        <x-admin.button type="button" variant="secondary" x-on:click="confirming = false">{{ __('admin.common.cancel') }}</x-admin.button>
                        <x-admin.button variant="danger">{{ __('admin_backup.confirm_go') }}</x-admin.button>
                    </div>
                </div>
            </div>
        </form>

        @if ($lastRun)
            <x-admin.alert tone="success" :title="__('admin_backup.last_run', ['when' => $lastRun['at']->timezone(config('store.display_timezone'))->format('j M Y, H:i'), 'by' => $lastRun['by']])">
                {{ $lastRun['summary'] }}
            </x-admin.alert>
        @endif
    </x-admin.form-section>

    {{-- Clean-up schedule --}}
    <x-admin.form-section :title="__('admin_backup.schedule_title')" :description="__('admin_backup.schedule_text')" class="mt-8">
        <form method="POST" action="{{ route('admin.settings.backup.update') }}" class="space-y-4" x-data="{ dirty: false }" @change="dirty = true">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="schedule">
            <fieldset>
                <legend class="mb-2 text-[13px] font-medium">{{ __('admin_backup.schedule_label') }}</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (['off', 'daily', 'weekly', 'monthly'] as $when)
                        <label class="cursor-pointer">
                            <input type="radio" name="schedule" value="{{ $when }}" class="peer sr-only" @checked($options['schedule'] === $when)>
                            <span class="inline-flex h-8 items-center rounded-lg border border-line-strong bg-surface px-3 text-[13px] font-medium text-ink-soft peer-checked:border-ink-strong peer-checked:bg-ink-strong peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ __('admin_backup.schedule.'.$when) }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-muted">{{ __('admin_backup.schedule_help') }}</p>
            </fieldset>
            <x-admin.button x-bind:disabled="! dirty">{{ __('admin.common.save') }}</x-admin.button>
        </form>
    </x-admin.form-section>
@endsection
