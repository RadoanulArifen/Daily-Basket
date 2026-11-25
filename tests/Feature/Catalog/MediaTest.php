<?php

namespace Tests\Feature\Catalog;

use App\Models\Media;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaTest extends CatalogTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! ImageProcessor::isSupported()) {
            $this->markTestSkipped('PHP GD with WebP support is needed.');
        }

        Storage::fake('public');
    }

    public function test_upload_converts_to_webp_in_several_sizes(): void
    {
        $response = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('Cushion Photo.jpg', 2400, 3000)])
            ->assertCreated()
            ->assertJsonStructure(['id', 'thumb', 'url', 'name']);

        $media = Media::findOrFail($response->json('id'));
        $this->assertSame(['thumb', 'medium', 'large'], array_keys($media->sizes));
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame(1280, $media->width);  // scaled to 1600 on the long side
        $this->assertSame(1600, $media->height);

        foreach ($media->sizes as $file) {
            Storage::disk('public')->assertExists($media->directory.'/'.$file);
        }

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($media->path('thumb')));
        $this->assertSame([256, 320], [$w, $h]);
    }

    public function test_small_images_are_not_enlarged(): void
    {
        $id = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('small.png', 300, 200)])->assertCreated()->json('id');

        $media = Media::findOrFail($id);
        $this->assertSame(['thumb'], array_keys($media->sizes));
        $this->assertStringEndsWith('thumb.webp', $media->path('large'));
    }

    public function test_non_images_are_rejected(): void
    {
        $this->postJson('/admin/media', ['file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertSame(0, Media::count());
    }

    public function test_images_attach_to_products_in_order_and_can_be_deleted(): void
    {
        $first = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('a.jpg', 800, 800)])->json('id');
        $second = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('b.jpg', 800, 800)])->json('id');

        $this->post('/admin/products', $this->productPayload(['media_ids' => [$second, $first]]))->assertSessionHasNoErrors();
        $product = \App\Models\Product::firstOrFail();
        $this->assertSame([$second, $first], $product->media->pluck('id')->all());

        $this->get('/admin/media')->assertOk()->assertSee('Used in 1 product');
        $this->get('/admin/media?format=json')->assertOk()->assertJsonCount(2, 'data');

        $this->put("/admin/media/{$first}", ['alt' => ['en' => 'Red cushion', 'ar' => 'وسادة حمراء']])->assertSessionHasNoErrors();
        $this->assertSame('وسادة حمراء', Media::find($first)->getTranslation('alt', 'ar'));

        $directory = Media::find($second)->directory;
        $this->delete("/admin/media/{$second}")->assertSessionHas('success');
        Storage::disk('public')->assertMissing($directory.'/thumb.webp');
        $this->assertSame([$first], $product->fresh()->media->pluck('id')->all());
    }
}
