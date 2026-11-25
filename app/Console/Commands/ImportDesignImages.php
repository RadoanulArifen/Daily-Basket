<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Services\Media\ImageProcessor;
use App\Services\Media\MediaService;
use App\Services\Settings\SettingsService;
use Database\Seeders\FurnivoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Downloads the photos the Furnivo design uses (hosted on Unsplash) into the media
 * library: products, category images, hero slides and the category strip. Safe to run
 * again — anything that already has an image is skipped.
 */
class ImportDesignImages extends Command
{
    protected $signature = 'store:import-design-images';

    protected $description = 'Download the active theme\'s design photos (Unsplash) into the media library';

    private int $done = 0;

    private int $failed = 0;

    public function handle(MediaService $media, SettingsService $settings): int
    {
        if (! ImageProcessor::isSupported()) {
            $this->error('PHP’s image extension (GD with WebP) is off — switch on extension=gd in php.ini first.');

            return self::FAILURE;
        }

        // A theme with its own photos brings a DesignImages class (app/Themes/<Theme>);
        // otherwise these are the Furnivo design's photos.
        $themed = 'App\\Themes\\'.\Illuminate\Support\Str::studly((string) config('store.theme')).'\\DesignImages';
        if (config('store.theme') !== 'furnivo' && class_exists($themed)) {
            app($themed)->run(fn (string $url, string $name, string $alt) => $this->download($media, $url, $name, $alt), fn (string $line) => $this->info($line));
            $this->report();

            return self::SUCCESS;
        }

        $data = json_decode(file_get_contents(FurnivoSeeder::DATA), true);
        $unsplash = fn (string $id, int $w) => 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w='.$w.'&q=80';

        $this->info('Products…');
        $bar = $this->output->createProgressBar(count($data['PRODUCTS']));
        foreach ($data['PRODUCTS'] as $p) {
            $product = Product::query()->where('slug', $p['id'])->with('media')->first();
            if ($product && $product->media->isEmpty() && str_starts_with($p['image'], 'http')) {
                $url = preg_replace('/([?&])w=\d+/', '$1w=1400', $p['image']);
                if ($id = $this->download($media, $url, $p['id'].'.jpg', $p['name'])) {
                    $product->media()->sync([$id => ['position' => 0]]);
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // Gallery, as in the design: the piece's own photo, then photos of other pieces in
        // the same category. Only products that still have a single photo are touched.
        $this->info('Galleries…');
        $main = Product::query()->with('media')->get()->mapWithKeys(fn ($pr) => [$pr->slug => [$pr, $pr->media->first()?->id]]);
        foreach ($data['PRODUCTS'] as $p) {
            [$product, $own] = $main[$p['id']] ?? [null, null];
            if (! $product || ! $own || $product->media->count() > 1) {
                continue;
            }
            $extra = collect($data['PRODUCTS'])->filter(fn ($o) => $o['cat'] === $p['cat'] && $o['id'] !== $p['id'])
                ->map(fn ($o) => $main[$o['id']][1] ?? null)->filter()->reject(fn ($id) => $id === $own)->unique()->take(3)->values();
            $product->media()->syncWithoutDetaching($extra->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1]])->all());
        }

        $this->info('Categories…');
        foreach ($data['CATIMG'] as $designId => $photo) {
            $category = Category::query()->where('slug', FurnivoSeeder::CATEGORY_SLUGS[$designId] ?? $designId)->first();
            if ($category && ! $category->image_id && ($id = $this->download($media, $unsplash($photo, 1600), $category->slug.'.jpg', $category->name))) {
                $category->forceFill(['image_id' => $id])->save();
            }
        }

        $this->info('Hero slides…');
        $banners = Banner::query()->where('placement', 'hero')->orderBy('sort_order')->get()->values();
        foreach ($data['HERO'] as $i => $slide) {
            $banner = $banners[$i] ?? null;
            if ($banner && ! $banner->image_id && ($id = $this->download($media, $unsplash($slide['img'], 2000), 'hero-'.($i + 1).'.jpg', $slide['title']))) {
                $banner->forceFill(['image_id' => $id])->save();
            }
        }

        $this->info('About page…');
        $about = \App\Models\Page::query()->where('slug', 'about')->first();
        if ($about && empty($about->image_ids)) {
            $ids = [];
            foreach (\Database\Seeders\FurnivoContentSeeder::ABOUT_IMAGES as $i => $photo) {
                if ($id = $this->download($media, $unsplash($photo, $i === 0 ? 1800 : 1100), 'about-'.($i + 1).'.jpg', ['Furnivo showroom', 'Craft detail', 'Material study'][$i])) {
                    $ids[] = $id;
                }
            }
            if ($ids) {
                $about->forceFill(['image_ids' => $ids])->save();
            }
        }

        $this->info('Category strip…');
        $tiles = (array) $settings->get('furnivo.explore', []);
        foreach ($tiles as $i => $tile) {
            $photo = collect($data['EXPLORE'] ?? [])->firstWhere('label', $tile['label'])['img'] ?? null;
            if ($photo && empty($tile['image_id']) && ($id = $this->download($media, $unsplash($photo, 1400), 'tile-'.($i + 1).'.jpg', $tile['label']))) {
                $tiles[$i]['image_id'] = $id;
            }
        }
        $settings->setMany('furnivo', ['explore' => $tiles]);

        $this->report();

        return self::SUCCESS;
    }

    private function report(): void
    {
        $this->newLine();
        $this->info("Downloaded {$this->done} image(s).");

        if ($this->failed) {
            $this->newLine();
            $this->warn("{$this->failed} image(s) could not be downloaded. Run the command again to retry them.");
            $this->line('  These photos come from the internet, so this usually means:');
            $this->line('   - no internet connection, or a firewall/proxy blocking it; or');
            $this->line('   - PHP cannot verify secure connections on Windows. Download the certificate');
            $this->line('     bundle from https://curl.se/ca/cacert.pem, save it (e.g. C:\\xampp\\php\\cacert.pem)');
            $this->line('     and in php.ini set curl.cainfo and openssl.cafile to that path, then restart Apache.');
            $this->line('  Products without a photo show an empty square in the admin and storefront.');
        }
    }

    private function download(MediaService $media, string $url, string $name, string $alt): ?int
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        // Downloads are kept, so later runs (e.g. the hourly demo reset) need no internet.
        $cached = storage_path('app/private/design-images/'.md5($url));
        try {
            if (is_file($cached) && filesize($cached) >= 1000) {
                $body = (string) file_get_contents($cached);
            } else {
                $response = Http::timeout(30)->retry(2, 1000, throw: false)->get($url);
                if (! $response->successful() || strlen($body = $response->body()) < 1000) {
                    throw new \RuntimeException($response->successful() ? 'empty file' : 'HTTP '.$response->status());
                }
                @mkdir(dirname($cached), 0775, true);
                @file_put_contents($cached, $body);
            }
            file_put_contents($tmp, $body);
            $id = $media->storeFromPath($tmp, $name, ['en' => $alt])->id;
            $this->done++;

            return $id;
        } catch (Throwable $e) {
            $this->failed++;
            $this->warn("  Couldn’t download {$name}: {$e->getMessage()}");

            return null;
        } finally {
            @unlink($tmp);
        }
    }
}
