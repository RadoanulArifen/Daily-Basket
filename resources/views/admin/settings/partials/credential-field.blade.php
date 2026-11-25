{{-- One credential: locked if set in .env, otherwise "enter new value" with the saved one masked. --}}
@php
    $credentials = app(\App\Services\Settings\Credentials::class);
    $locked = $credentials->fromServer($group, $field);
    $current = $credentials->display($group, $field);
    $secret = ! in_array("{$group}.{$field}", \App\Services\Settings\Credentials::PLAIN, true);
@endphp
<div>
    <label for="cred-{{ $group }}-{{ $field }}" class="mb-1 flex items-center justify-between gap-2 text-[13px] font-medium">
        <span>{{ $label }}</span>
        @if ($locked)
            <x-admin.badge>{{ __('admin.credentials.from_server') }}</x-admin.badge>
        @elseif ($current)
            <x-admin.badge tone="success" dot="full">{{ __('admin.credentials.saved_badge') }}</x-admin.badge>
        @endif
    </label>
    <input id="cred-{{ $group }}-{{ $field }}" name="{{ $field }}" type="{{ $secret ? 'password' : 'text' }}" autocomplete="off" spellcheck="false" dir="ltr"
           @disabled($locked)
           placeholder="{{ $locked ? $current : ($current ? __('admin.credentials.replace', ['current' => $current]) : ($placeholder ?? '')) }}"
           @class(['admin-field font-mono text-[13px]', 'admin-field-error' => $errors->has($field)])>
    @error($field)<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror
</div>
