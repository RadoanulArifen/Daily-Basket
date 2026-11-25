<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandsAndSubscribersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_a_brand_can_be_created_edited_and_seen_in_the_store(): void
    {
        $this->post('/admin/brands', [
            'name' => ['en' => 'Loom Collection'], 'origin' => ['en' => 'London, UK'],
            'description' => ['en' => 'Considered, tactile furniture.'], 'is_active' => 1, 'position' => 1,
        ])->assertRedirect();

        $brand = Brand::query()->sole();
        $this->assertSame('loom-collection', $brand->slug, 'The web address is made from the name.');

        $this->get('/admin/brands')->assertOk()->assertSee('Loom Collection')->assertSee('London, UK');

        $this->put("/admin/brands/{$brand->id}", [
            'name' => ['en' => 'Loom'], 'origin' => ['en' => 'Manchester, UK'], 'is_active' => 1, 'position' => 2,
        ])->assertRedirect();

        $this->assertSame('Manchester, UK', $brand->fresh()->origin);
        $this->get("/brands/{$brand->slug}")->assertOk()->assertSee('Loom');
    }

    public function test_a_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake(config('store.uploads.disk', 'public'));

        $this->post('/admin/brands', [
            'name' => ['en' => 'Ebarza'], 'is_active' => 1,
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
        ])->assertRedirect();

        $brand = Brand::query()->sole();
        $this->assertNotNull($brand->logo);
        Storage::disk(config('store.uploads.disk', 'public'))->assertExists($brand->logo);

        $this->put("/admin/brands/{$brand->id}", ['name' => ['en' => 'Ebarza'], 'is_active' => 1, 'remove_logo' => 1])->assertRedirect();
        $this->assertNull($brand->fresh()->logo);
    }

    public function test_deleting_a_brand_keeps_its_products(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);

        $brand = Brand::query()->whereHas('products')->firstOrFail();
        $productCount = Product::query()->where('brand_id', $brand->id)->count();
        $this->assertGreaterThan(0, $productCount);

        $this->delete("/admin/brands/{$brand->id}")->assertRedirect();

        $this->assertNull(Brand::query()->find($brand->id));
        $this->assertSame($productCount, Product::query()->whereNull('brand_id')->count(), 'The pieces stay, without a brand.');
    }

    public function test_a_hidden_brand_is_not_in_the_store(): void
    {
        $this->post('/admin/brands', ['name' => ['en' => 'Quiet Maker'], 'is_active' => 0])->assertRedirect();

        $this->get('/brands/'.Brand::query()->sole()->slug)->assertNotFound();
    }

    public function test_subscribers_are_listed_and_can_be_exported(): void
    {
        Subscriber::query()->create(['email' => 'one@example.com', 'source' => 'footer']);
        Subscriber::query()->create(['email' => 'two@example.com', 'source' => 'checkout', 'unsubscribed_at' => now()]);

        $this->get('/admin/subscribers')->assertOk()->assertSee('one@example.com')->assertDontSee('two@example.com');
        $this->get('/admin/subscribers?status=unsubscribed')->assertOk()->assertSee('two@example.com');
        $this->get('/admin/subscribers?q=one')->assertOk()->assertSee('one@example.com');

        $csv = $this->get('/admin/subscribers/export');
        $csv->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $body = $csv->streamedContent();
        $this->assertStringContainsString('one@example.com', $body);
        $this->assertStringNotContainsString('two@example.com', $body, 'The export follows the tab you are on.');
    }

    public function test_a_subscriber_can_be_removed(): void
    {
        $subscriber = Subscriber::query()->create(['email' => 'one@example.com', 'source' => 'footer']);

        $this->delete("/admin/subscribers/{$subscriber->id}")->assertRedirect();

        $this->assertSame(0, Subscriber::query()->count());
    }
}
