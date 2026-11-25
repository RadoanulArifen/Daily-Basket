<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\MenuItem;
use App\Services\Currency\CurrencyService;
use App\Services\Navigation\MenuRepository;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Laravel 13 will not unserialize objects from a real cache store, so cached
 * data must be plain arrays. Tests use the "array" store, which hides this,
 * so here we force a serializing store (file).
 */
class CacheSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'file']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_currencies_survive_a_trip_through_the_cache(): void
    {
        $this->seed(CurrencySeeder::class);

        (new CurrencyService)->all();           // fills the cache
        $fromCache = (new CurrencyService)->all(); // reads it back

        $this->assertInstanceOf(Currency::class, $fromCache->get('AED'));
        $this->assertTrue($fromCache->get('AED')->is_base);
        $this->assertSame('دولار أمريكي', $fromCache->get('USD')->getTranslation('name', 'ar'));
    }

    public function test_menus_survive_a_trip_through_the_cache(): void
    {
        $this->seed(MenuSeeder::class);

        (new MenuRepository)->all();
        $header = (new MenuRepository)->get('header');

        $this->assertInstanceOf(MenuItem::class, $header->tree->first());
        $living = $header->tree->first(fn ($item) => $item->getTranslation('label', 'en') === 'Living');
        $this->assertCount(3, $living->children);
    }

    public function test_storefront_renders_with_a_real_cache_store(): void
    {
        $this->seed([CurrencySeeder::class, MenuSeeder::class]);

        $this->get('/')->assertOk();
        $this->get('/ar')->assertOk()->assertSee('غرفة المعيشة');
    }
}
