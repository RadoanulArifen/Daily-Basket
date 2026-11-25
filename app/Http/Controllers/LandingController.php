<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A plain HTML page with its own files, served at /landing/.
 *
 * Put the page in resources/landing/: index.html plus any folders it uses (css/, js/,
 * images/ …). Paths inside the HTML stay relative, exactly as in your download:
 * "assets/style.css" is served from /landing/assets/style.css.
 *
 * /landing and /landing.html redirect to /landing/, so relative paths always resolve.
 * The page is outside the shop: no password gate, and the shop's strict script policy
 * is not applied, so the page's own inline scripts, fonts and CDNs keep working.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): Response
    {
        $root = realpath(self::root());
        abort_unless($root !== false, 404);

        $path = trim((string) $path, '/');

        // "/landing" (no slash): send to "/landing/" so the page's relative links work.
        if ($path === '' && ! str_ends_with($request->getPathInfo(), '/')) {
            return redirect()->to(url('landing').'/'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        // Nothing hidden (.env, .git …) and nothing outside the folder.
        abort_if(collect(explode('/', $path))->contains(fn ($part) => str_starts_with($part, '.')), 404);
        $file = realpath($root.DIRECTORY_SEPARATOR.$path);
        abort_unless($file !== false && str_starts_with($file, $root), 404);

        if (is_dir($file)) {
            // A sub-folder: same rule as the top, then its index.html.
            if ($path !== '' && ! str_ends_with($request->getPathInfo(), '/')) {
                return redirect()->to(url('landing/'.$path).'/', 301);
            }
            $file .= DIRECTORY_SEPARATOR.'index.html';
            abort_unless(is_file($file), 404);
        }

        // Pages are small: sent whole. Other files (images, fonts, video) are streamed.
        if ($this->isHtml($file)) {
            return response((string) file_get_contents($file), 200, $this->headers($file));
        }

        return response()->file($file, $this->headers($file));
    }

    /** A theme's own landing page (resources/landing/<theme>) wins; otherwise resources/landing. */
    public static function root(): string
    {
        $theme = (string) config('store.theme');

        return $theme !== '' && is_file(resource_path("landing/{$theme}/index.html"))
            ? resource_path("landing/{$theme}")
            : resource_path('landing');
    }

    /** @return array<string, string> */
    private function headers(string $file): array
    {
        $html = $this->isHtml($file);

        $headers = [
            'Content-Type' => $this->type($file),
            'Cache-Control' => $html ? 'no-cache' : 'public, max-age=86400',
        ];

        if ($html) {
            // Keeps the shop's strict policy (nonce-only scripts) off this page; only framing is limited.
            $headers['Content-Security-Policy'] = 'frame-ancestors '.\App\Support\FrameAncestors::directive()."; base-uri 'self'; object-src 'none'";
            if (! config('store.landing_indexable')) {
                $headers['X-Robots-Tag'] = 'noindex';
            }
        }

        return $headers;
    }

    private function isHtml(string $file): bool
    {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['html', 'htm'], true);
    }

    private function type(string $file): string
    {
        $types = [
            'html' => 'text/html; charset=UTF-8', 'htm' => 'text/html; charset=UTF-8', 'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8', 'mjs' => 'text/javascript; charset=UTF-8', 'json' => 'application/json',
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'avif' => 'image/avif', 'ico' => 'image/x-icon', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'txt' => 'text/plain; charset=UTF-8',
            'pdf' => 'application/pdf', 'xml' => 'application/xml',
        ];

        return $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? (mime_content_type($file) ?: 'application/octet-stream');
    }
}
