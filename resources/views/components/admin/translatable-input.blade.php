{{--
    One field, one value per store language, switched with small tabs.
    Submits as name[en], name[ar]. Opens on the first language with an error.
--}}
@props([
    'name',
    'label',
    'values' => [],
    'multiline' => false,
    'rich' => false,       // HTML editor (bold, headings, lists, links); cleaned on save
    'rows' => 3,
    'help' => null,
    'required' => false,   // required in the default language only
    'maxlength' => null,
])

@php
    // Only the languages the store has switched on.
    $locales = app(\App\Support\Localization::class)->locales();
    $default = config('store.default_locale');
    $values = is_array($values) ? $values : [$default => $values];
    $baseId = str_replace(['[', ']', '.'], '_', $name);
    $errorLocale = collect(array_keys($locales))->first(fn ($code) => $errors->has("{$name}.{$code}"));
    $start = $errorLocale ?? $default;
@endphp

<div x-data="{ tab: @js($start) }" {{ $attributes->class('space-y-1.5') }}>
    <div class="flex items-end justify-between gap-3">
        <x-admin.label :for="$baseId.'_'.$start" :required="$required">{{ $label }}</x-admin.label>

        <div class="inline-flex rounded-md bg-canvas p-0.5 text-xs" role="tablist" aria-label="{{ __('admin.common.language') }}">
            @foreach ($locales as $code => $meta)
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === @js($code)"
                    @click="tab = @js($code)"
                    :class="tab === @js($code) ? 'bg-surface text-ink shadow-xs' : 'text-muted hover:text-ink'"
                    class="rounded px-2 py-1 font-medium transition"
                    lang="{{ $code }}"
                >
                    {{ $meta['native'] }}
                    @if ($errors->has("{$name}.{$code}"))
                        <span class="ms-1 inline-block size-1.5 rounded-full bg-danger-600 align-middle" aria-hidden="true"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    @foreach ($locales as $code => $meta)
        @php
            $fieldName = "{$name}[{$code}]";
            $key = "{$name}.{$code}";
            $id = "{$baseId}_{$code}";
            $hasError = $errors->has($key);
            $current = old($key, $values[$code] ?? null);
        @endphp

        <div x-show="tab === @js($code)" @if ($code !== $start) x-cloak @endif>
            @unless ($rich)<label for="{{ $id }}" class="sr-only">{{ $label }} ({{ $meta['name'] }})</label>@endunless

            @if ($rich)
                {{-- TinyMCE (resources/js/admin/rich-text.js) turns this into the HTML editor, with a
                     Source code button to switch to HTML; without JavaScript it stays a plain text box.
                     Pictures go to the media library. --}}
                <textarea name="{{ $fieldName }}" id="{{ $id }}" rows="{{ $rows }}" dir="{{ $meta['dir'] }}" lang="{{ $code }}" data-rich
                    aria-label="{{ $label }} ({{ $meta['name'] }})"
                    data-upload-url="{{ route('admin.media.store') }}" data-library-url="{{ route('admin.media.index', ['format' => 'json']) }}"
                    data-labels="{{ json_encode(__('admin.editor')) }}"
                    @class(['admin-field admin-rich', 'admin-field-error' => $hasError])>{{ $current }}</textarea>
            @elseif ($multiline)
                <textarea name="{{ $fieldName }}" id="{{ $id }}" rows="{{ $rows }}" dir="{{ $meta['dir'] }}" lang="{{ $code }}"
                    @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                    @if ($required && $code === $default) required @endif
                    @class(['admin-field', 'admin-field-error' => $hasError])>{{ $current }}</textarea>
            @else
                <input type="text" name="{{ $fieldName }}" id="{{ $id }}" value="{{ $current }}" dir="{{ $meta['dir'] }}" lang="{{ $code }}"
                    @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                    @if ($required && $code === $default) required @endif
                    @class(['admin-field', 'admin-field-error' => $hasError])>
            @endif

            <x-admin.field-error :name="$key" />
        </div>
    @endforeach

    @if ($help)
        <p class="text-[13px] text-muted">{{ $help }}</p>
    @endif
</div>
