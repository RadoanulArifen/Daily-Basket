{{-- Google Analytics 4 (Admin → Settings → Tracking). Storefront only; never in the admin. --}}
@if ($gaId = settings()->get('tracking.ga_measurement_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}"></script>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($gaId), { currency: @js(config('store.base_currency')) });
    </script>
@endif
