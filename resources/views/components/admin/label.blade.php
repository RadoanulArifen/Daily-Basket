@props(['for' => null, 'required' => false])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->class('block text-sm font-medium text-ink') }}>
    {{ $slot }}
    @if ($required)<span class="text-danger-600" aria-hidden="true">*</span>@endif
</label>
