<?php

namespace App\Services\Theme;

use App\Models\Admin;
use App\Models\CodeVersion;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Vite;
use Throwable;

/**
 * Online Store → Custom code (Owner only): extra CSS, code for <head> and the end of <body>,
 * and the external domains those scripts may load from. Only the storefront uses it.
 */
class CustomCode
{
    public function __construct(private SettingsService $settings) {}

    public function get(): array
    {
        try {
            $code = $this->settings->group('code');
        } catch (Throwable) {
            $code = [];
        }

        return ['css' => (string) ($code['css'] ?? ''), 'head' => (string) ($code['head'] ?? ''), 'body' => (string) ($code['body'] ?? ''), 'domains' => (array) ($code['domains'] ?? [])];
    }

    /** Saves the code and keeps a version to restore later. */
    public function save(array $code, ?Admin $admin): void
    {
        $code['domains'] = self::cleanDomains($code['domains'] ?? []);
        $this->settings->setMany('code', $code);

        CodeVersion::create($code + ['admin_id' => $admin?->id]);
        CodeVersion::query()->whereNotIn('id', CodeVersion::query()->latest('id')->limit(CodeVersion::KEEP)->pluck('id'))->delete();
    }

    /** CSS that can't close its own <style> tag. */
    public function css(): string
    {
        return str_ireplace('</style', '<\\/style', $this->get()['css']);
    }

    /** Head / body code with this page's security nonce added to every <script>. */
    public function html(string $part): string
    {
        $html = $this->get()[$part] ?? '';
        if ($html === '') {
            return '';
        }
        $nonce = Vite::cspNonce();

        return (string) preg_replace('/<script\b(?![^>]*\bnonce=)/i', '<script nonce="'.$nonce.'"', $html);
    }

    /** "https://connect.facebook.net/en_US/fbevents.js" → "https://connect.facebook.net" */
    public static function cleanDomains(array|string $domains): array
    {
        $lines = is_array($domains) ? $domains : preg_split('/[\s,]+/', $domains);

        return collect($lines)->map(function ($d) {
            $d = trim((string) $d);
            if ($d === '') {
                return null;
            }
            $host = parse_url(str_contains($d, '://') ? $d : 'https://'.$d, PHP_URL_HOST);

            return $host && preg_match('/^(\*\.)?[a-z0-9.-]+\.[a-z]{2,}$/i', $host) ? 'https://'.strtolower($host) : null;
        })->filter()->unique()->values()->take(30)->all();
    }
}
