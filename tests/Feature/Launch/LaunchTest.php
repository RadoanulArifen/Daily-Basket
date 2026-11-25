<?php

namespace Tests\Feature\Launch;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Storefront\StorefrontTestCase;

class LaunchTest extends StorefrontTestCase
{
    public function test_security_headers_are_sent_everywhere(): void
    {
        foreach (['/', '/shop', '/admin/login', '/sitemap.xml'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('Strict-Transport-Security'); // only in production over HTTPS
        }

        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->get('/admin/login')->assertHeader('X-Frame-Options', 'DENY'); // the admin's stricter value is kept
    }

    public function test_hsts_is_sent_in_production_over_https(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => false]);

        $this->get('https://localhost/robots.txt')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_old_carts_are_pruned_but_recent_and_customer_carts_kept(): void
    {
        $old = Cart::create(['token' => 'old']);
        $recent = Cart::create(['token' => 'recent']);
        $customer = Cart::create(['token' => 'mine', 'user_id' => User::factory()->create()->id]);
        Cart::query()->whereKey([$old->id, $customer->id])->update(['updated_at' => now()->subDays(45)]);

        $this->artisan('model:prune', ['--model' => [Cart::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($customer); // customers' bags are kept for 6 months
    }

    public function test_launch_check_fails_on_a_development_setup_and_explains_why(): void
    {
        $this->artisan('store:launch-check')
            ->expectsOutputToContain('Debug mode is off')
            ->expectsOutputToContain('Set APP_ENV=production')
            ->expectsOutputToContain('must be fixed')
            ->assertFailed();
    }

    public function test_launch_check_passes_a_production_setup(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.debug' => false, 'app.url' => 'https://example.com', 'session.secure' => true,
            'mail.default' => 'smtp', 'mail.from.address' => 'store@furnivo.com', 'queue.default' => 'database',
        ]);
        settings()->setMany('general', ['store_email' => 'hello@example.com', 'trn' => '100000000000003']);
        Cache::forever('schedule:heartbeat', now()->toIso8601String());
        @mkdir(public_path('build'), 0777, true);
        $manifest = public_path('build/manifest.json');
        $created = ! file_exists($manifest) && file_put_contents($manifest, '{}');

        try {
            $this->artisan('store:launch-check')->expectsOutputToContain('Ready')->assertSuccessful();
        } finally {
            if ($created) {
                unlink($manifest);
            }
        }
    }

    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
