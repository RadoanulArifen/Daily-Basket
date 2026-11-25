{{-- Google reCAPTCHA v3: adds a token to every POST form on the page, so the forms look
     exactly the same but the server can tell people from bots. Only loads when keys are set. --}}
@php $recaptcha = app(\App\Services\Security\Recaptcha::class); @endphp
@if ($recaptcha->enabled())
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha->siteKey() }}" async defer></script>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (() => {
            const KEY = @js($recaptcha->siteKey());
            const token = (form) => new Promise((resolve) => {
                if (! window.grecaptcha?.execute) return resolve(null);
                window.grecaptcha.ready(() => window.grecaptcha.execute(KEY, { action: 'submit' }).then(resolve).catch(() => resolve(null)));
            });

            const attach = async (form) => {
                const value = await token(form);
                if (! value) return;
                let field = form.querySelector('input[name="g-recaptcha-response"]');
                if (! field) {
                    field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'g-recaptcha-response';
                    form.appendChild(field);
                }
                field.value = value;
            };

            // Keep a fresh token on every form, and refresh it before it expires (2 minutes).
            const forms = () => [...document.querySelectorAll('form[method="POST" i], form[method="post"]')];
            const refresh = () => forms().forEach(attach);
            window.addEventListener('load', refresh);
            setInterval(refresh, 110000);
            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (form.matches('form[method="POST" i]') && ! form.querySelector('input[name="g-recaptcha-response"]')) attach(form);
            }, true);
        })();
    </script>
@endif
