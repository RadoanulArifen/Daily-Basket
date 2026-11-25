@props(['tone' => 'success']) {{-- success | warning | error --}}

@php $tone = $tone === 'danger' ? 'error' : $tone; @endphp

<div role="{{ $tone === 'error' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex items-start gap-3 rounded-md border px-4 py-3 text-sm',
    'border-success/20 bg-success/5 text-success' => $tone === 'success',
    'border-brand-red/20 bg-brand-red/5 text-brand-red' => $tone === 'warning',
    'border-danger/20 bg-danger/5 text-danger' => $tone === 'error',
]) }}>
    <x-icon :name="match ($tone) { 'error' => 'circle-x', 'warning' => 'triangle-alert', default => 'circle-check' }" class="mt-px size-5" />
    <div class="text-ink">{{ $slot }}</div>
</div>
