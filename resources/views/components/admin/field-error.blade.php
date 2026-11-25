@props(['name', 'bag' => 'default'])

@php $key = str_replace(['[', ']'], ['.', ''], $name); @endphp

@error($key, $bag)
    <p {{ $attributes->class('mt-1.5 flex items-start gap-1.5 text-[13px] text-danger-600') }} id="{{ $key }}-error">
        <x-icon name="circle-x" class="mt-px size-4" />
        <span>{{ $message }}</span>
    </p>
@enderror
