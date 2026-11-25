@props([
    'name',
    'label' => null,
    'value' => null,
    'help' => null,
    'required' => false,
    'rows' => 3,
])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
    $hasError = $errors->has($key);
@endphp

<div {{ $attributes->only('class')->class('space-y-1.5') }}>
    @if ($label)
        <x-admin.label :for="$id" :required="$required">{{ $label }}</x-admin.label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $key }}-error" @endif
        {{ $attributes->except(['class', 'id'])->class(['admin-field', 'admin-field-error' => $hasError]) }}
    >{{ old($key, $value) }}</textarea>

    @if ($help && ! $hasError)
        <p class="text-[13px] text-muted">{{ $help }}</p>
    @endif

    <x-admin.field-error :name="$name" />
</div>
