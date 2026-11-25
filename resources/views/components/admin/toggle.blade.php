@props([
    'name',
    'label',
    'checked' => false,
    'description' => null,
    'disabled' => false,
])

@php
    $id = $attributes->get('id', $name);
    $isOn = (bool) old($name, $checked);
@endphp

<div {{ $attributes->only('class')->class('flex items-start justify-between gap-4') }}>
    <div class="min-w-0">
        <label for="{{ $id }}" class="text-sm font-medium text-ink">{{ $label }}</label>
        @if ($description)
            <p class="mt-0.5 text-[13px] text-muted">{{ $description }}</p>
        @endif
        <x-admin.field-error :name="$name" />
    </div>

    <span class="relative inline-flex shrink-0">
        <input type="hidden" name="{{ $name }}" value="0" @disabled($disabled)>
        <input
            type="checkbox"
            role="switch"
            name="{{ $name }}"
            id="{{ $id }}"
            value="1"
            @checked($isOn)
            @disabled($disabled)
            {{ $attributes->except(['class', 'id']) }}
            class="peer absolute inset-0 z-10 size-full cursor-pointer appearance-none rounded-full disabled:cursor-not-allowed"
        >
        <span aria-hidden="true" class="h-6 w-11 rounded-full bg-line-strong transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2 peer-disabled:opacity-50"></span>
        <span aria-hidden="true" class="absolute start-0.5 top-0.5 size-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
    </span>
</div>
