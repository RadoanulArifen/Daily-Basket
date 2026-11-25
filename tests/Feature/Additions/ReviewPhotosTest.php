<?php

namespace Tests\Feature\Additions;

use App\Models\Admin;
use App\Models\Media;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Storefront\StorefrontTestCase;

class ReviewPhotosTest extends StorefrontTestCase
{
    public function test_customers_add_photos_that_stay_out_of_the_media_library(): void
    {
        if (! ImageProcessor::isSupported()) {
            $this->markTestSkipped('GD with WebP is needed.');
        }
        Storage::fake('public');
        $this->makeProduct('Dallah');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/products/dallah/reviews', [
            'rating' => 5, 'body' => 'Beautiful and heavy.',
            'photos' => [UploadedFile::fake()->image('a.jpg', 900, 900), UploadedFile::fake()->image('b.png', 600, 800)],
        ])->assertSessionHasNoErrors();

        $review = ProductReview::with('photos')->sole();
        $this->assertCount(2, $review->photos);
        $this->assertSame('reviews', $review->photos[0]->collection);

        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->get('/admin/media?format=json')->assertJsonCount(0, 'data');
        $this->get('/admin/reviews')->assertSee($review->photos[0]->url('thumb'), false);

        $this->put("/admin/reviews/{$review->id}", ['status' => 'approved']);
        auth('admin')->logout();
        $this->get('/products/dallah')->assertSee($review->photos[1]->url('thumb'), false);

        $this->actingAs($admin, 'admin')->delete("/admin/reviews/{$review->id}");
        $this->assertSame(0, Media::count());
    }

    public function test_photo_limits(): void
    {
        $this->makeProduct('Dallah');
        $photos = array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 400, 400), range(1, 4));

        $this->actingAs(User::factory()->create())->post('/products/dallah/reviews', ['rating' => 5, 'body' => 'Beautiful and heavy.', 'photos' => $photos])
            ->assertSessionHasErrors('photos');
        $this->actingAs(User::factory()->create())->post('/products/dallah/reviews', ['rating' => 5, 'body' => 'Beautiful and heavy.', 'photos' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0');
    }
}
