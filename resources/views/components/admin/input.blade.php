@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'prefix' => null,
    'suffix' => null,
    'bag' => 'default',
])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
    $hasError = $errors->getBag($bag)->has($key);
    $current = $type === 'password' ? null : old($key, $value);
@endphp

<div {{ $attributes->only('class')->class('space-y-1.5') }}>
    @if ($label)
        <x-admin.label :for="$id" :required="$required">{{ $label }}</x-admin.label>
    @endif

    <div class="relative flex">
        @if ($prefix)
            <span class="inline-flex items-center rounded-s-lg border border-e-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $prefix }}</span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            @if (! is_null($current)) value="{{ $current }}" @endif
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $key }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
            {{ $attributes->except(['class', 'id'])->class([
                'admin-field',
                'admin-field-error' => $hasError,
                'rounded-s-none' => $prefix,
                'rounded-e-none' => $suffix,
            ]) }}
        >

        @if ($suffix)
            <span class="inline-flex items-center rounded-e-lg border border-s-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $suffix }}</span>
        @endif
    </div>

    @if ($help && ! $hasError)
        <p id="{{ $id }}-help" class="text-[13px] text-muted">{{ $help }}</p>
    @endif

    <x-admin.field-error :name="$name" :bag="$bag" />
</div>
