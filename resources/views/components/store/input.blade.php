@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->class('space-y-2') }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-ink">
        {{ $label }}
        @unless ($required)<span class="font-normal text-stone">({{ __('store.common.optional') }})</span>@endunless
    </label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
        {{ $attributes->except(['class', 'id'])->class(['store-field', 'store-field-error' => $hasError]) }}
    >
    @if ($help && ! $hasError)
        <p id="{{ $id }}-help" class="text-[13px] text-stone">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="text-[13px] text-danger">{{ $message }}</p>
    @enderror
</div>
