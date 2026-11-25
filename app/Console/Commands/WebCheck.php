<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Opens the live site the way a browser does and reports what would break pages:
 *
 *   php artisan store:web-check                      (uses APP_URL)
 *   php artisan store:web-check https://shop.example.com
 *
 * - every page answers 200;
 * - exactly one Content-Security-Policy header, and it is ours (a second policy added by the web
 *   server, hosting panel or CDN blocks the store's scripts — browsers enforce both);
 * - the CSP nonce in the header matches the one in the page (if not, a proxy or CDN is caching
 *   HTML, and every script that relies on the nonce is refused);
 * - every script, stylesheet and image the pages use loads (no 403 / 404).
 */
class WebCheck extends Command
{
    protected $signature = 'store:web-check {url? : Site address, default APP_URL} {--no-verify : Skip the HTTPS certificate check (self-signed certificates)}';

    protected $description = 'Load the storefront and admin over HTTP and report blocked scripts, missing files and header problems';

    private array $rows = [];

    public function handle(): int
    {
        $base = rtrim((string) ($this->argument('url') ?: config('app.url')), '/');
        if (! preg_match('#^https?://#', $base)) {
            $this->error('Give the full site address, like https://shop.example.com');

            return self::FAILURE;
        }

        $pages = ['Home page' => '/'];
        try {
            if ($product = Product::query()->active()->value('slug')) {
                $pages['Product page'] = $this->path(store_route('products.show', ['slug' => $product]));
            }
        } catch (Throwable) {
            // No database here: the home page and admin sign-in are still worth checking.
        }
        $pages['Cart'] = $this->path(store_route('cart'));
        $pages['Admin sign-in'] = '/'.trim((string) config('store.admin_path'), '/').'/login';

        $files = [];
        foreach ($pages as $label => $path) {
            $response = $this->get($base.$path);
            if (! $response) {
                $this->row(false, "{$label} ({$path})", 'Could not connect. Is the address right, and can this server reach itself? Try: curl -I '.$base.$path);

                continue;
            }

            $this->row($response->status() === 200, "{$label} answers 200", "It answered {$response->status()}");
            if ($response->status() !== 200) {
                continue;
            }

            $html = $response->body();
            $this->checkPolicy($label, $response, $html);

            $this->row(! str_contains($html, '/livewire-'), "{$label}: no separate livewire.js",
                'The page still loads Livewire’s own script: clear old compiled views — php artisan optimize:clear && php artisan optimize');

            foreach ($this->assets($html, $base) as $url) {
                $files[$url] = true;
            }
        }

        $broken = [];
        foreach (array_slice(array_keys($files), 0, 150) as $url) {
            $response = $this->get($url);
            $status = $response?->status() ?? 0;
            if ($status !== 200) {
                $broken[] = [$status ?: 'no answer', $url];
            }
        }
        $this->row($broken === [], count($files).' scripts, styles and images load', count($broken).' do not — see the list below');

        $this->table(['', 'Check', 'What to do'], $this->rows);

        if ($broken) {
            $this->newLine();
            $this->table(['Status', 'File'], $broken);
            $this->line('404 on /build/… → upload the whole public/build folder (or run npm ci && npm run build), then php artisan optimize.');
            $this->line('404 on /storage/… → run php artisan storage:link (and check the image files are in storage/app/public).');
            $this->line('403 → a web-server, hosting-panel or firewall rule blocks the file: check its error log for the rule.');
        }

        $fails = collect($this->rows)->where(0, 'FAIL')->count();
        if ($fails) {
            $this->error("{$fails} problem(s) found.");

            return self::FAILURE;
        }

        $this->info('The site loads cleanly.');

        return self::SUCCESS;
    }

    private function checkPolicy(string $label, Response $response, string $html): void
    {
        $policies = $response->toPsrResponse()->getHeader('Content-Security-Policy');   // any letter case
        // Some servers fold repeated headers into one line, separated by a comma.
        $count = collect($policies)->sum(fn ($line) => preg_match_all("/(^|,)\s*default-src/i", $line));

        $this->row($count === 1, "{$label}: one Content-Security-Policy",
            $count === 0 ? 'No policy header arrived — something strips it on the way.'
                : 'The web server, hosting panel or CDN adds a second policy, and browsers obey both, so the store’s scripts are refused. Remove the extra Content-Security-Policy header there (the app sends its own).');

        $policy = implode(',', $policies);
        preg_match("/'nonce-([^']+)'/", $policy, $header);
        preg_match('/<script[^>]+nonce="([^"]+)"/', $html, $page);
        if ($header && $page) {
            $this->row($header[1] === $page[1], "{$label}: script nonce matches",
                'The page HTML is served from a cache (CDN, proxy or hosting cache), so its nonce is stale and the browser refuses the scripts. Turn off HTML caching for this site (static files can stay cached).');
        }
    }

    /** Same-site script, stylesheet and image addresses used by a page. */
    private function assets(string $html, string $base): array
    {
        $host = parse_url($base, PHP_URL_HOST);
        preg_match_all('/<(?:script|img|source)[^>]+\bsrc="([^"]+)"/i', $html, $src);
        preg_match_all('/<link[^>]+rel="(?:stylesheet|modulepreload|preload|icon)"[^>]*href="([^"]+)"|<link[^>]+href="([^"]+)"[^>]*rel="(?:stylesheet|modulepreload|preload|icon)"/i', $html, $links);
        preg_match_all('/url\((?:&quot;|["\'])?([^)"\'&]+)(?:&quot;|["\'])?\)/i', $html, $css);

        return collect([...$src[1], ...$links[1], ...$links[2], ...$css[1]])
            ->filter()
            ->map(fn ($url) => html_entity_decode($url))
            ->map(fn ($url) => str_starts_with($url, '//') ? parse_url($base, PHP_URL_SCHEME).':'.$url : (str_starts_with($url, '/') ? $base.$url : $url))
            ->filter(fn ($url) => str_starts_with($url, 'http') && parse_url($url, PHP_URL_HOST) === $host)
            ->unique()->values()->all();
    }

    private function get(string $url): ?Response
    {
        try {
            return Http::withOptions(['verify' => ! $this->option('no-verify'), 'allow_redirects' => true])
                ->withHeaders(['User-Agent' => 'store-web-check', 'Accept' => 'text/html,*/*'])
                ->timeout(20)->get($url);
        } catch (Throwable) {
            return null;
        }
    }

    private function path(string $url): string
    {
        return (parse_url($url, PHP_URL_PATH) ?: '/').(($q = parse_url($url, PHP_URL_QUERY)) ? '?'.$q : '');
    }

    private function row(bool $ok, string $label, string $fix): void
    {
        $this->rows[] = [$ok ? 'OK' : 'FAIL', $label, $ok ? '' : $fix];
    }
}
