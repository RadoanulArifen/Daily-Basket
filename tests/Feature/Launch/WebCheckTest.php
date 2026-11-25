<?php

namespace Tests\Feature\Launch;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** php artisan store:web-check — loads the site over HTTP and reports what would block scripts. */
class WebCheckTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private function page(string $nonce = 'abc123'): string
    {
        return '<html><head><script type="module" src="https://shop.test/build/assets/app-1.js" nonce="'.$nonce.'"></script>'
            .'<link rel="stylesheet" href="/build/assets/app-1.css"></head><body><img src="/themes/freshnest/avatar-0.png"></body></html>';
    }

    private function policy(string $nonce = 'abc123'): string
    {
        return "default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'";
    }

    public function test_a_clean_site_passes(): void
    {
        Http::fake([
            'shop.test/build/*' => Http::response('x'),
            'shop.test/themes/*' => Http::response('x'),
            'shop.test/*' => Http::response($this->page(), 200, ['Content-Type' => 'text/html', 'Content-Security-Policy' => $this->policy()]),
        ]);

        $this->artisan('store:web-check', ['url' => 'https://shop.test'])->expectsOutputToContain('The site loads cleanly.')->assertSuccessful();
    }

    public function test_a_second_policy_from_the_server_is_reported(): void
    {
        Http::fake([
            'shop.test/build/*' => Http::response('x'),
            'shop.test/themes/*' => Http::response('x'),
            'shop.test/*' => Http::response($this->page(), 200, ['Content-Security-Policy' => [$this->policy(), "default-src 'self'; script-src 'self'"]]),
        ]);

        $this->artisan('store:web-check', ['url' => 'https://shop.test'])->expectsOutputToContain('adds a second policy')->assertFailed();
    }

    public function test_cached_html_with_a_stale_nonce_and_missing_files_are_reported(): void
    {
        Http::fake([
            'shop.test/build/*' => Http::response('', 404),
            'shop.test/themes/*' => Http::response('', 403),
            'shop.test/*' => Http::response($this->page('old'), 200, ['Content-Security-Policy' => $this->policy('new')]),
        ]);

        $this->artisan('store:web-check', ['url' => 'https://shop.test'])
            ->expectsOutputToContain('served from a cache')
            ->expectsOutputToContain('https://shop.test/build/assets/app-1.js')
            ->assertFailed();
    }

    public function test_launch_check_spots_a_half_uploaded_build(): void
    {
        $dir = public_path('build');
        $manifest = $dir.'/manifest.json';
        $backup = is_file($manifest) ? file_get_contents($manifest) : null;
        @mkdir($dir, 0777, true);
        file_put_contents($manifest, json_encode(['resources/js/x.js' => ['file' => 'assets/not-uploaded-123.js']]));

        try {
            $this->artisan('store:launch-check')->expectsOutputToContain('assets/not-uploaded-123.js')->assertFailed();
        } finally {
            $backup === null ? @unlink($manifest) : file_put_contents($manifest, $backup);
        }
    }

    public function test_launch_check_knows_which_php_builds_have_the_php2shell_fix(): void
    {
        $patched = fn (string $v) => \App\Console\Commands\LaunchCheck::phpIsPatched($v);

        foreach (['8.2.33', '8.2.34', '8.3.35', '8.4.26', '8.5.11', '8.1.31', '7.4.33'] as $old) {
            $this->assertFalse($patched($old), $old);
        }
        foreach (['8.2.35', '8.3.36', '8.4.27', '8.4.30', '8.5.12', '8.6.0'] as $fixed) {
            $this->assertTrue($patched($fixed), $fixed);
        }
    }
}
