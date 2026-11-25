{{--
    Language and currency pickers (top bar and footer). Each shows only when there is a
    choice: the language picker when Arabic is switched on (Settings → Store details →
    Languages), the currency picker when more than one currency is active (Settings →
    Currencies). $on = 'light' (top bar) or 'dark' (footer).
--}}
@php
    $localization = app(\App\Support\Localization::class);
    $languages = $localization->locales();
    $herePath = '/'.ltrim(preg_replace('#^/'.preg_quote($localization->current(), '#').'(/|$)#', '/', '/'.ltrim(request()->path(), '/')), '/');
    $query = request()->getQueryString();
    $currencySvc = app(\App\Services\Currency\CurrencyService::class);
    $available = $currencySvc->active();
    $dark = ($on ?? 'light') === 'dark';
    $fg = $dark ? '#B4B9BF' : '#5E656B';
    $select = 'appearance:none; -webkit-appearance:none; border:0; background:transparent; color:'.$fg.'; font:inherit; font-size:13px; font-weight:600; cursor:pointer; padding:0; padding-inline-end:16px; outline:none;';
    $names = ['en' => 'English', 'ar' => 'العربية'];
@endphp
@if (count($languages) > 1 || $available->count() > 1)
<div style="display:flex; align-items:center; gap:18px; font-size:13px; color:{{ $fg }};">
  @if (count($languages) > 1)
    <label style="position:relative; display:inline-flex; align-items:center; gap:6px;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $dark ? 'var(--fn-accent)' : 'var(--fn-primary)' }}" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M3.5 12h17M12 3.5c2.4 2.6 3.5 5.4 3.5 8.5s-1.1 5.9-3.5 8.5c-2.4-2.6-3.5-5.4-3.5-8.5s1.1-5.9 3.5-8.5Z"></path></svg>
      <span class="fn-sr">{{ __('store.common.language') }}</span>
      <select x-data @change="window.location = $event.target.value" style="{{ $select }}">
        @foreach ($languages as $code => $meta)
          <option value="{{ $localization->url($herePath, $code).($query ? '?'.$query : '') }}" @selected($code === $localization->current()) style="color:var(--fn-ink);">{{ $names[$code] ?? strtoupper($code) }}</option>
        @endforeach
      </select>
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position:absolute; inset-inline-end:2px; pointer-events:none;"><path d="m6 9 6 6 6-6"></path></svg>
    </label>
  @endif
  @if ($available->count() > 1)
    <form method="POST" action="{{ route('currency.switch') }}" style="margin:0;">
      @csrf
      <label style="position:relative; display:inline-flex; align-items:center; gap:6px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $dark ? 'var(--fn-accent)' : 'var(--fn-primary)' }}" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M14.8 9.2c-.5-.9-1.6-1.4-2.8-1.4-1.6 0-2.8.8-2.8 2s1.2 1.7 2.8 2 2.8.8 2.8 2-1.2 2-2.8 2c-1.3 0-2.4-.6-2.9-1.5M12 6.3v1.5M12 16.2v1.5"></path></svg>
        <span class="fn-sr">{{ __('store.common.currency') }}</span>
        <select name="currency" x-data @change="$el.form.submit()" style="{{ $select }}">
          @foreach ($available as $currency)
            <option value="{{ $currency->code }}" @selected($currency->code === $currencySvc->current()->code) style="color:var(--fn-ink);">{{ $currency->code }}</option>
          @endforeach
        </select>
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position:absolute; inset-inline-end:2px; pointer-events:none;"><path d="m6 9 6 6 6-6"></path></svg>
      </label>
    </form>
  @endif
</div>
@endif
