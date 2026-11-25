{{-- A filter shown as a pill: "Payment status ▾" → options. Must sit inside the index-filters GET form. --}}
@props(['name', 'label', 'options'])

@php $value = request()->query($name); @endphp

<label @class([
    'relative inline-flex h-8 items-center rounded-lg text-[13px] font-medium',
    'bg-[#e3e3e3] text-ink-strong' => filled($value),
    'border border-dashed border-line-strong text-ink-soft hover:border-muted' => blank($value),
])>
    <span class="pointer-events-none flex items-center gap-1 ps-2.5 pe-7">
        @if (filled($value)){{ $label }}: <strong class="font-semibold">{{ $options[$value] ?? $value }}</strong>@else{{ $label }}@endif
    </span>
    <x-icon name="chevron-down" class="pointer-events-none absolute end-2 size-3.5" />
    <select name="{{ $name }}" x-data @change="$el.form.submit()" class="absolute inset-0 cursor-pointer opacity-0" aria-label="{{ $label }}">
        <option value="">{{ __('admin.views.any') }}</option>
        @foreach ($options as $key => $optionLabel)
            <option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</label>
