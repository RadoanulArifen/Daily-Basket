<?php

namespace App\Services\Settings;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Payment and courier credentials entered in the admin.
 *
 * - Stored encrypted (with APP_KEY) in the settings table; the full value is never shown again.
 * - A value set in .env always wins and appears as "set on the server" (locked) in the admin.
 * - At boot, saved values are copied into config('services.…'), so StripeGateway and
 *   AramexCarrier read them exactly as they read .env values.
 */
class Credentials
{
    public const FIELDS = [
        'stripe' => ['key', 'secret', 'webhook_secret'],
        'aramex' => ['username', 'password', 'account_number', 'account_pin', 'account_entity', 'account_country'],
        'smtp' => ['host', 'port', 'encryption', 'username', 'password', 'from_address', 'from_name'],
        'recaptcha' => ['site_key', 'secret'],
    ];

    /**
     * Where a field lands in config. Most are services.<group>.<field>; the mail server
     * settings have to go where Laravel's mailer reads them.
     */
    public const CONFIG_KEYS = [
        'smtp.host' => 'mail.mailers.smtp.host',
        'smtp.port' => 'mail.mailers.smtp.port',
        'smtp.encryption' => 'mail.mailers.smtp.encryption',
        'smtp.username' => 'mail.mailers.smtp.username',
        'smtp.password' => 'mail.mailers.smtp.password',
        'smtp.from_address' => 'mail.from.address',
        'smtp.from_name' => 'mail.from.name',
    ];

    /** Fields that aren't secret: shown in full. */
    public const PLAIN = ['aramex.username', 'aramex.account_number', 'aramex.account_entity', 'aramex.account_country', 'stripe.key',
        'smtp.host', 'smtp.port', 'smtp.encryption', 'smtp.username', 'smtp.from_address', 'smtp.from_name', 'recaptcha.site_key'];

    /** The env variable a field can be set with on the server, when it isn't services.*. */
    private const ENV_KEYS = [
        'smtp.host' => 'MAIL_HOST', 'smtp.port' => 'MAIL_PORT', 'smtp.encryption' => 'MAIL_SCHEME',
        'smtp.username' => 'MAIL_USERNAME', 'smtp.password' => 'MAIL_PASSWORD',
        'smtp.from_address' => 'MAIL_FROM_ADDRESS', 'smtp.from_name' => 'MAIL_FROM_NAME',
    ];

    public static function configKey(string $group, string $field): string
    {
        return self::CONFIG_KEYS["{$group}.{$field}"] ?? "services.{$group}.{$field}";
    }

    /** @var array<string, bool> "group.field" => set in .env */
    private array $fromServer = [];

    public function __construct(private SettingsService $settings) {}

    /** Copy saved credentials into config, unless .env already provides them. Called at boot. */
    public function apply(): void
    {
        try {
            $saved = $this->settings->group('credentials');
        } catch (Throwable) {
            return; // e.g. first install, before the settings table exists
        }

        foreach (self::FIELDS as $group => $fields) {
            foreach ($fields as $field) {
                $configKey = self::configKey($group, $field);
                $this->fromServer["{$group}.{$field}"] = filled(config($configKey)) && ! $this->isDefault($group, $field);

                if (! $this->fromServer["{$group}.{$field}"] && filled($saved["{$group}_{$field}"] ?? null)) {
                    $value = $this->decrypt($saved["{$group}_{$field}"]);
                    if ($value !== null) {
                        config([$configKey => $value]);
                        if ($group === 'smtp' && $field === 'host') {
                            // Saved mail server details mean email goes out over SMTP.
                            config(['mail.default' => 'smtp']);
                        }
                    }
                }
            }
        }
    }

    public function fromServer(string $group, string $field): bool
    {
        return $this->fromServer["{$group}.{$field}"] ?? false;
    }

    /** What the admin shows: full value for non-secrets, "••••4f2a" for secrets, null when not set. */
    public function display(string $group, string $field): ?string
    {
        $value = (string) config(self::configKey($group, $field));
        if ($value === '') {
            return null;
        }

        if (config('store.demo')) {
            return '••••••••';   // public demo: show that a value is saved, never any part of it
        }

        return in_array("{$group}.{$field}", self::PLAIN, true) ? $value : '••••'.mb_substr($value, -4);
    }

    /**
     * Save new values; empty fields keep what's there. Fields set in .env are ignored.
     *
     * @param  array<string, ?string>  $values  field => new value
     * @return list<string> the fields that changed
     */
    public function save(string $group, array $values): array
    {
        $changed = [];
        $store = [];

        foreach (self::FIELDS[$group] as $field) {
            $value = trim((string) ($values[$field] ?? ''));
            if ($value === '' || $this->fromServer($group, $field)) {
                continue;
            }
            $store["{$group}_{$field}"] = Crypt::encryptString($value);
            config([self::configKey($group, $field) => $value]);
            if ($group === 'smtp' && $field === 'host') {
                config(['mail.default' => 'smtp']);
            }
            $changed[] = $field;
        }

        if ($store) {
            $this->settings->setMany('credentials', $store);
        }

        return $changed;
    }

    /** Remove saved values for a group (values from .env stay). */
    public function forget(string $group): void
    {
        $this->settings->setMany('credentials', collect(self::FIELDS[$group])
            ->reject(fn ($f) => $this->fromServer($group, $f))
            ->mapWithKeys(fn ($f) => ["{$group}_{$f}" => null])->all());

        foreach (self::FIELDS[$group] as $field) {
            if (! $this->fromServer($group, $field)) {
                config([self::configKey($group, $field) => null]);
            }
        }
    }

    private function decrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null; // APP_KEY changed: the value must be entered again
        }
    }

    /** Config defaults (e.g. entity "DXB") don't count as "set on the server". */
    private function isDefault(string $group, string $field): bool
    {
        if ($group === 'aramex' && in_array($field, ['account_entity', 'account_country'], true)) {
            return blank(env('ARAMEX_'.strtoupper($field)));
        }

        // The mail fields count as "set on the server" only when .env names a real mail
        // host. A fresh Laravel .env carries placeholders (127.0.0.1, port 2525,
        // hello@example.com) which would otherwise lock these fields for no reason.
        if ($group === 'smtp') {
            $host = (string) env('MAIL_HOST');

            return blank($host) || in_array($host, ['127.0.0.1', 'localhost', 'mailpit', 'mailhog', 'null', 'log'], true);
        }

        return false;
    }
}
