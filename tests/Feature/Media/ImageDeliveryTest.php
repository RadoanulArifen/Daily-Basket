<?php

namespace Tests\Feature\Media;

use App\Models\Media;
use App\Support\StorageLink;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageDeliveryTest extends TestCase
{
    private function media(): Media
    {
        return (new Media)->forceFill([
            'disk' => 'public', 'directory' => 'media/test', 'original_name' => 'chair.jpg',
            'sizes' => ['thumb' => 'thumb.webp', 'large' => 'large.webp'], 'mime_type' => 'image/webp',
            'width' => 1400, 'height' => 1400, 'size_bytes' => 1000,
        ]);   // not saved: these addresses are worked out from the record itself
    }

    public function test_image_addresses_work_on_any_host(): void
    {
        // Full addresses break when the site is opened on another host or port: the browser's
        // image policy (img-src 'self') blocks them. They must be relative.
        $this->assertSame('/storage/media/test/thumb.webp', $this->media()->url('thumb'));
    }

    public function test_full_addresses_where_they_leave_the_browser(): void
    {
        $this->assertStringStartsWith(config('app.url').'/storage/', $this->media()->absoluteUrl('large'));
    }

    public function test_images_are_served_when_the_storage_link_is_missing(): void
    {
        if (file_exists(public_path('storage'))) {
            $this->markTestSkipped('The public/storage link exists here, so the fallback is not registered.');
        }

        Storage::disk('public')->put('media/test/thumb.webp', 'not-really-an-image');

        $this->get('/storage/media/test/thumb.webp')->assertOk();
        $this->get('/storage/media/test/missing.webp')->assertNotFound();
    }

    public function test_the_storage_link_state_is_judged_by_where_it_leads(): void
    {
        $link = public_path('storage');
        $backup = $link.'.backup-'.getmypid();
        $existed = is_link($link) || file_exists($link);
        if ($existed) {
            rename($link, $backup);
        }

        try {
            clearstatcache(true, $link);
            $this->assertSame(StorageLink::MISSING, StorageLink::state());

            touch($link);   // a plain file in the way
            $this->assertSame(StorageLink::BROKEN, StorageLink::state());
            unlink($link);

            symlink(sys_get_temp_dir().'/vibe-not-here-'.getmypid(), $link);   // leads nowhere
            $this->assertSame(StorageLink::BROKEN, StorageLink::state());
            unlink($link);

            symlink(sys_get_temp_dir(), $link);   // leads to the wrong folder
            $this->assertSame(StorageLink::WRONG_TARGET, StorageLink::state());
            unlink($link);

            symlink(storage_path('app/public'), $link);
            $this->assertSame(StorageLink::OK, StorageLink::state());
            unlink($link);
        } finally {
            if ($existed) {
                rename($backup, $link);
            }
        }
    }

    public function test_admin_and_storefront_show_the_same_site_icon(): void
    {
        config(['store.theme' => 'furnivo']);

        $this->assertStringContainsString('themes/furnivo/favicon.png', view('partials.favicons')->render());
        foreach (['resources/views/admin/layouts/app.blade.php', 'resources/views/admin/layouts/auth.blade.php'] as $layout) {
            $this->assertStringContainsString("@include('partials.favicons')", file_get_contents(base_path($layout)));
        }
    }
}
