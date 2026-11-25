<?php

namespace Tests\Feature\Analytics;

use App\Models\Admin;
use App\Support\Geo\GeoIp;
use App\Support\Geo\MmdbReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Storefront\StorefrontTestCase;

/**
 * Analytics → Traffic: visits recorded by RecordTraffic, bots tallied apart, the report and
 * the live JSON. tests/Fixtures/geo-test.mmdb is a tiny DB-IP-style file made with Python
 * mmdb-writer (81.2.69.0/24 = Dubai, 5.62.0.0/16 = Riyadh, 2a02:c7c::/32 = Manchester…).
 */
class TrafficTest extends StorefrontTestCase
{
    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put(GeoIp::FILE, file_get_contents(base_path('tests/Fixtures/geo-test.mmdb')));
    }

    private function visit(string $path = '/', string $ip = '81.2.69.142', string $ua = self::BROWSER, array $headers = [])
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => $ua, 'Accept-Language' => 'en-GB,en;q=0.9'] + $headers)
            ->get($path);
    }

    public function test_the_reader_matches_the_test_database(): void
    {
        $reader = new MmdbReader(GeoIp::path());
        $this->assertSame('Dubai', $reader->get('81.2.69.142')['city']['names']['en']);
        $this->assertSame('SA', $reader->get('5.62.10.1')['country']['iso_code']);
        $this->assertSame('Manchester', $reader->get('2a02:c7c:1::5')['city']['names']['en']);
        $this->assertEqualsWithDelta(25.2048, $reader->get('81.2.69.1')['location']['latitude'], 0.0001);
        $this->assertSame('دولة', $reader->get('81.2.69.1')['country']['names']['ar']);
        $this->assertNull($reader->get('8.8.8.8'));
        $this->assertNull($reader->get('2001:db8::1'));
    }

    public function test_a_visit_is_recorded_with_country_and_city_and_no_ip(): void
    {
        $this->visit('/?utm_source=insta&email=me@x.test')->assertOk();
        $this->visit('/cart')->assertOk();

        $this->assertSame(1, DB::table('traffic_visits')->count());
        $row = (array) DB::table('traffic_visits')->first();
        $this->assertSame('AE', $row['country']);
        $this->assertSame('Dubai', $row['city']);
        $this->assertSame(2, (int) $row['pages']);
        $this->assertSame('/cart', $row['path']);
        $this->assertTrue((bool) $row['is_new']);
        $this->assertSame(16, strlen($row['visitor']));

        // No IP address and no query string anywhere in the table.
        $dump = json_encode(DB::table('traffic_visits')->get());
        $this->assertStringNotContainsString('81.2.69', $dump);
        $this->assertStringNotContainsString('utm_source', $dump);
        $this->assertStringNotContainsString('me@x.test', $dump);
    }

    public function test_a_visit_ends_after_thirty_quiet_minutes(): void
    {
        $this->visit('/');
        $this->travel(31)->minutes();
        $this->visit('/');

        $this->assertSame(2, DB::table('traffic_visits')->count());
        $this->assertFalse((bool) DB::table('traffic_visits')->orderByDesc('id')->value('is_new'));   // a returning visitor
    }

    public function test_each_bot_kind_is_tallied_apart_and_never_as_a_visit(): void
    {
        $this->visit('/', ua: 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->visit('/', ua: 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)');
        $this->visit('/', ua: 'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)');
        $this->visit('/', ua: 'curl/8.5.0');
        $this->withServerVariables(['REMOTE_ADDR' => '5.62.1.1'])->withHeaders(['User-Agent' => self::BROWSER, 'Accept-Language' => ''])->get('/');   // looks like a browser, no languages

        $this->assertSame(0, DB::table('traffic_visits')->count());
        $this->assertSame(['ai' => 2, 'fake' => 1, 'other' => 1, 'search' => 1], DB::table('traffic_bot_counts')->orderBy('kind')->pluck('hits', 'kind')->map(fn ($n) => (int) $n)->all());
    }

    public function test_admins_ajax_prefetch_and_errors_are_not_counted(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->visit('/');
        auth('admin')->logout();

        $this->visit('/', headers: ['X-Requested-With' => 'XMLHttpRequest']);
        $this->visit('/', headers: ['Sec-Purpose' => 'prefetch']);
        $this->visit('/no-such-page-at-all');
        $this->withServerVariables(['REMOTE_ADDR' => '81.2.69.142'])->withHeaders(['User-Agent' => self::BROWSER, 'Accept-Language' => 'en'])->post('/currency', ['currency' => 'AED']);

        $this->assertSame(0, DB::table('traffic_visits')->count());
        $this->assertSame(0, DB::table('traffic_bot_counts')->count());
    }

    public function test_without_the_ip_file_visits_still_count(): void
    {
        Storage::disk('local')->delete(GeoIp::FILE);
        $this->visit('/');

        $this->assertSame(1, DB::table('traffic_visits')->count());
        $this->assertNull(DB::table('traffic_visits')->value('country'));

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/analytics/traffic')->assertOk()->assertSee('php artisan store:geoip-update');
    }

    public function test_the_page_and_live_json(): void
    {
        $this->visit('/', '81.2.69.10');
        $this->visit('/cart', '5.62.3.3');
        $this->visit('/', '2a02:c7c::9');
        $this->visit('/', ua: 'Mozilla/5.0 (compatible; bingbot/2.0)');

        $this->actingAs(Admin::factory()->create(['role' => 'manager']), 'admin');   // analytics is open to managers
        $page = $this->get('/admin/analytics/traffic?period=today')->assertOk();
        $page->assertSee('On the shop now')->assertSee('Saudi Arabia')->assertSee('United Kingdom')->assertSee('IP data by DB-IP')
            ->assertSee('data-cc="AE"', false);
        $report = $page->viewData('report');
        $this->assertSame(3, $report['stats']['visitors']);
        $this->assertSame(3, $report['stats']['live']);
        $this->assertSame(3, $report['stats']['countries']);
        $this->assertSame(1, $report['stats']['bots']['search']);

        $json = $this->getJson('/admin/analytics/traffic/live')->assertOk()->json();
        $this->assertSame(3, $json['count']);
        $this->assertSame(['Dubai, United Arab Emirates', 'Manchester, United Kingdom', 'Riyadh, Saudi Arabia'], collect($json['live'])->pluck('place')->sort()->values()->all());
        $this->assertEqualsWithDelta(24.7136, collect($json['live'])->firstWhere('cc', 'SA')['lat'], 0.0001);

        $this->get('/admin/analytics')->assertSee(route('admin.traffic'));

        $this->actingAs(Admin::factory()->create(['role' => 'orders']), 'admin');
        $this->get('/admin/analytics/traffic')->assertForbidden();
        $this->get('/admin/analytics/traffic/live')->assertForbidden();
    }

    public function test_geoip_update_swaps_in_a_good_file_and_keeps_the_old_one_on_a_bad_download(): void
    {
        $good = gzencode(file_get_contents(base_path('tests/Fixtures/geo-test.mmdb')));
        Storage::disk('local')->delete(GeoIp::FILE);

        Http::fake([now()->format('Y-m').'*' => Http::response('', 404), '*' => Http::response($good)]);
        $this->artisan('store:geoip-update')->assertSuccessful();
        $this->assertTrue(Storage::disk('local')->exists(GeoIp::FILE));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'dbip-city-lite-'.now()->subMonthNoOverflow()->format('Y-m').'.mmdb.gz'));

        $before = Storage::disk('local')->get(GeoIp::FILE);
        Http::fake(['*' => Http::response(gzencode('not a database'))]);
        $this->artisan('store:geoip-update')->assertFailed();
        $this->assertSame($before, Storage::disk('local')->get(GeoIp::FILE));
        $this->assertFalse(Storage::disk('local')->exists(GeoIp::FILE.'.part'));
    }

    public function test_old_traffic_is_part_of_the_statistics_clean_up(): void
    {
        DB::table('traffic_visits')->insert(['visitor' => str_repeat('a', 16), 'pages' => 1, 'path' => '/', 'is_new' => true, 'started_at' => now()->subYears(2), 'last_seen_at' => now()->subYears(2)]);
        DB::table('traffic_bot_counts')->insert(['day' => now()->subYears(2)->toDateString(), 'kind' => 'ai', 'hits' => 3]);
        $this->assertSame(2, app(\App\Services\Maintenance\Cleanup::class)->counts()['statistics']);
    }
}
