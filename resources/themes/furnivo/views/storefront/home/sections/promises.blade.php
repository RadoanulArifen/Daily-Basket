{{-- Promise cards: the same lines as the trust badges, given room. --}}
@php
    // The setting may be stored as an array of lines or as one block of text.
    $badges = settings()->get('theme.trust_badges');
    $promises = collect(is_array($badges) ? $badges : preg_split('/\r\n|\r|\n/', (string) $badges))
        ->map(fn ($line) => trim((string) $line))->filter()->take(4)->values();
    $promises = $promises->isNotEmpty() ? $promises : collect(__('store.theme.promise_lines'));
@endphp
@if ($promises->isNotEmpty())
    <section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(44px, 5vw, 72px) 0px;" aria-label="{{ __('store.theme.promises') }}">
        <div style="{{ $pad }}; display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            @foreach ($promises as $promise)
                <div style="border: 1px solid rgb(236, 236, 236); padding: 24px;">
                    <p style="margin: 0px; font-size: 15px; line-height: 1.6; color: rgb(29, 29, 29);">{{ $promise }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
