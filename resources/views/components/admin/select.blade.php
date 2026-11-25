@props([
    'name',
    'label' => null,
    'options' => [],   // [value => label]
    'value' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
    $hasError = $errors->has($key);
    $selected = (string) old($key, $value);
@endphp

<div {{ $attributes->only('class')->class('space-y-1.5') }}>
    @if ($label)
        <x-admin.label :for="$id" :required="$required">{{ $label }}</x-admin.label>
    @endif

    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $id }}"
            @if ($required) required @endif
            {{ $attributes->except(['class', 'id'])->class(['admin-field appearance-none pe-9', 'admin-field-error' => $hasError]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-icon name="chevron-down" class="pointer-events-none absolute end-2.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
    </div>

    @if ($help && ! $hasError)
        <p class="text-[13px] text-muted">{{ $help }}</p>
    @endif

    <x-admin.field-error :name="$name" />
</div>
