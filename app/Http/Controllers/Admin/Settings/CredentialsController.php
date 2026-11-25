<?php

// SECURITY: Super Admin (AdminRole::Owner = store owner) only — checked in every mutating
// action, on top of auth:admin + AuthorizeAdminArea (area: settings). Secrets are stored encrypted.

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AuditLog;
use App\Services\Payments\StripeGateway;
use App\Services\Settings\Credentials;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\AramexCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Throwable;

/** Stripe and Aramex account details, entered in the admin (Owner only). */
class CredentialsController extends AdminController
{
    public function update(Request $request, string $group, Credentials $credentials, SettingsService $settings): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->role === \App\Enums\AdminRole::Owner, 403);   // credentials: Super Admin (store owner) only
        abort_unless(array_key_exists($group, Credentials::FIELDS), 404);

        $rules = match ($group) {
            'smtp' => [
                'host' => ['nullable', 'string', 'max:180'],
                'port' => ['nullable', 'integer', 'between:1,65535'],
                'encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
                'username' => ['nullable', 'string', 'max:180'],
                'password' => ['nullable', 'string', 'max:255'],
                'from_address' => ['nullable', 'email', 'max:180'],
                'from_name' => ['nullable', 'string', 'max:120'],
            ],
            'recaptcha' => [
                'site_key' => ['nullable', 'string', 'max:120'],
                'secret' => ['nullable', 'string', 'max:120'],
            ],
            default => null,
        };

        $rules ??= $group === 'stripe' ? [
            'key' => ['nullable', 'string', 'max:255', 'regex:/^pk_(test|live)_[A-Za-z0-9]+$/'],
            'secret' => ['nullable', 'string', 'max:255', 'regex:/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/'],
            'webhook_secret' => ['nullable', 'string', 'max:255', 'regex:/^whsec_[A-Za-z0-9]+$/'],
        ] : [
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:40'],
            'account_pin' => ['nullable', 'string', 'max:40'],
            'account_entity' => ['nullable', 'string', 'max:10'],
            'account_country' => ['nullable', 'string', 'size:2'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'origin_city' => ['required', Rule::in(array_values(AramexCarrier::CITIES))],
            'product_type' => ['required', 'string', 'max:10'],
            'eta.*' => ['nullable', 'string', 'max:80'],
        ];
        $data = $request->validate($rules, ['*.regex' => __('admin.credentials.format')]);

        // Keys must match each other: both test or both live.
        if ($group === 'stripe') {
            $key = $data['key'] ?? config('services.stripe.key');
            $secret = $data['secret'] ?? config('services.stripe.secret');
            if ($key && $secret && str_contains((string) $key, '_live_') !== str_contains((string) $secret, '_live_')) {
                return back()->withErrors(['secret' => __('admin.credentials.mixed_modes')]);
            }
        }

        $changed = $credentials->save($group, $data);

        if ($group === 'aramex') {
            $before = $settings->group('shipping');
            $settings->setMany('shipping', [
                'aramex_mode' => $data['mode'],
                'aramex_origin_city' => $data['origin_city'],
                'aramex_product_type' => strtoupper($data['product_type']),
                'aramex_eta' => array_filter($data['eta'] ?? []),
            ]);
            // New details or another mode must be tested again before customers see Aramex.
            if ($changed || ($before['aramex_mode'] ?? 'test') !== $data['mode'] || ($before['aramex_origin_city'] ?? 'Dubai') !== $data['origin_city']) {
                $settings->setMany('shipping', ['aramex_tested_at' => null, 'aramex_enabled' => false]);
            }
            Cache::flush();   // cached quotes belong to the old details
        }

        if ($changed) {
            AuditLog::record('updated', 'credentials', null, $group, collect($changed)->mapWithKeys(fn ($f) => [$f => ['••••', '••••']])->all());
        }

        return back()->with('success', __('admin.credentials.saved'));
    }

    public function test(string $group, StripeGateway $stripe, AramexCarrier $aramex, SettingsService $settings): RedirectResponse
    {
        abort_unless(array_key_exists($group, Credentials::FIELDS), 404);

        if ($group === 'stripe') {
            $result = $stripe->testConnection();

            return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
        }

        // Mail server: send a real message to any address, and explain a failure in plain words.
        if ($group === 'smtp') {
            return $this->testMail();
        }

        if (! $aramex->isConfigured()) {
            return back()->with('error', __('admin.credentials.aramex_missing'));
        }

        try {
            $price = $aramex->quote('dubai', 1.0);
            $settings->setMany('shipping', ['aramex_tested_at' => now()->toIso8601String()]);

            return back()->with('success', __('admin.credentials.aramex_ok', ['price' => price_admin($price)]));
        } catch (Throwable $e) {
            $settings->setMany('shipping', ['aramex_tested_at' => null, 'aramex_enabled' => false]);

            return back()->with('error', __('admin.credentials.aramex_failed', ['message' => $e->getMessage()]));
        }
    }

    /** Switch live Aramex rates on or off at checkout (only after a successful test). */
    public function toggleAramex(Request $request, SettingsService $settings): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->role === \App\Enums\AdminRole::Owner, 403);   // credentials: Super Admin (store owner) only
        $on = $request->boolean('enabled');
        if ($on && blank($settings->get('shipping.aramex_tested_at'))) {
            return back()->with('error', __('admin.credentials.test_first'));
        }
        $settings->setMany('shipping', ['aramex_enabled' => $on]);

        return back()->with('success', __($on ? 'admin.credentials.aramex_on' : 'admin.credentials.aramex_off'));
    }

    public function forget(string $group, Credentials $credentials, SettingsService $settings): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->role === \App\Enums\AdminRole::Owner, 403);   // credentials: Super Admin (store owner) only
        abort_unless(array_key_exists($group, Credentials::FIELDS), 404);
        $credentials->forget($group);

        if ($group === 'aramex') {
            $settings->setMany('shipping', ['aramex_tested_at' => null, 'aramex_enabled' => false]);
        }
        if ($group === 'stripe' && blank(config('services.stripe.secret'))) {
            $settings->setMany('payments', ['stripe_enabled' => false]);
        }
        AuditLog::record('deleted', 'credentials', null, $group);

        return back()->with('success', __('admin.credentials.removed'));
    }

    /**
     * Settings → Mail server → "Send test". The result (sent, or the reason it was not) is
     * shown on the page under the button, not as a passing notice.
     */
    private function testMail(): RedirectResponse
    {
        $data = request()->validate(['to' => ['nullable', 'email', 'max:190']]);
        $admin = auth('admin')->user();
        $to = ($data['to'] ?? null) ?: $admin?->email;
        $back = redirect()->to(route('admin.settings.mail-server.edit').'#mail-test');
        $host = (string) config('mail.mailers.smtp.host');

        if (blank($host) || blank($to)) {
            return $back->with('mail_test', ['ok' => false, 'to' => $to, 'reason' => __('admin_mail.no_host'), 'fixes' => [], 'details' => null]);
        }

        // Don't leave the page hanging for a minute when the port is blocked.
        config(['mail.mailers.smtp.timeout' => 15]);
        $mailer = (string) config('mail.default');
        \Illuminate\Support\Facades\Mail::purge($mailer);
        $started = microtime(true);

        try {
            \Illuminate\Support\Facades\Mail::mailer($mailer)->to($to)->send(new \App\Mail\TestEmail((string) ($admin?->name ?? '')));
        } catch (Throwable $e) {
            report($e);
            $why = \App\Support\MailErrorExplainer::explain($e, $host, config('mail.mailers.smtp.port'), config('mail.mailers.smtp.encryption') ?: config('mail.mailers.smtp.scheme'));

            return $back->with('mail_test', ['ok' => false, 'to' => $to, 'details' => mb_strimwidth($e->getMessage(), 0, 1000, '…')] + $why);
        }

        return $back->with('mail_test', ['ok' => true, 'to' => $to, 'seconds' => number_format(microtime(true) - $started, 1)]);
    }
}
