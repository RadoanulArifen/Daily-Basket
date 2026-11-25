<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Explains why images may not be showing, and what to do about it. */
class CheckImages extends Command
{
    protected $signature = 'store:check-images';

    protected $description = 'Check that uploaded images can be created and shown';

    /** On Windows a junction needs no administrator rights and stays up to date. */
    private static function windowsFix(): string
    {
        return windows_os()
            ? 'Run "php artisan store:link-images" (it makes a Windows junction, which needs no administrator rights, and clears any leftover entry first).'
            : 'Run "php artisan store:link-images" (or "php artisan storage:link").';
    }

    public function handle(): int
    {
        $problems = [];   // images are broken
        $notes = [];      // images work, but something is worth fixing

        $this->line('<comment>Image support</comment>');
        if (ImageProcessor::isSupported()) {
            $this->line('  PHP can create WebP images: yes');
        } else {
            $this->line('  PHP can create WebP images: <fg=red>no</>');
            $problems[] = 'Enable the PHP "gd" extension with WebP support (in php.ini remove the ; before extension=gd), then restart Apache. Uploads fail without it.';
        }

        $this->line('<comment>Where images are served from</comment>');
        $link = public_path('storage');
        switch (\App\Support\StorageLink::state()) {
            case \App\Support\StorageLink::OK:
                $this->line('  public/storage link: yes → '.(@realpath($link) ?: storage_path('app/public')));
                if (\App\Support\StorageLink::opaqueToPhp()) {
                    $this->line('    (a Windows junction: the web server serves images through it. PHP itself');
                    $this->line('     cannot read through junctions, so the PHP fallback stays on as a safety net.)');
                }
                break;
            case \App\Support\StorageLink::MISSING:
                $this->line('  public/storage link: <fg=yellow>missing</> — images are served through PHP instead, which works but is slower');
                $notes[] = self::windowsFix();
                break;
            case \App\Support\StorageLink::BROKEN:
                $this->line('  public/storage link: <fg=yellow>there, but it leads nowhere</> — images are served through PHP instead, which works but is slower');
                $notes[] = self::windowsFix().' It removes the leftover entry for you.';
                break;
            default:
                $this->line('  public/storage link: <fg=red>points to the wrong folder</>');
                $notes[] = 'It must point to storage\\app\\public. '.self::windowsFix().' It removes the leftover entry for you.';
        }

        $url = Storage::disk('public')->url('example.webp');
        $this->line('  image address: '.$url);
        if (preg_match('#^https?://#', $url)) {
            $host = parse_url($url, PHP_URL_HOST);
            $this->line('  <fg=yellow>These are full addresses.</> Images only show when the site is opened on exactly '.$host.'.');
            $problems[] = 'Remove STORAGE_URL from .env (or set it to /storage) so image addresses work on any address, then run "php artisan config:clear".';
        }

        $this->line('<comment>Stored images</comment>');
        $total = Media::query()->count();
        $missing = [];
        Media::query()->select(['id', 'disk', 'directory', 'sizes', 'original_name'])->chunkById(200, function ($rows) use (&$missing) {
            foreach ($rows as $media) {
                foreach (array_keys((array) $media->sizes) as $size) {
                    $path = $media->path($size);
                    if ($path && ! Storage::disk($media->disk)->exists($path)) {
                        $missing[] = $media->original_name.' ('.$size.')';
                        break;
                    }
                }
            }
        });
        $this->line('  images in the database: '.$total);
        $this->line('  files missing on disk: '.(count($missing) ?: '0'));
        foreach (array_slice($missing, 0, 5) as $name) {
            $this->line('    - '.$name);
        }
        if ($missing) {
            $problems[] = count($missing).' image file(s) are in the database but not on disk. Re-upload them, or for the demo catalogue run "php artisan store:import-design-images".';
        }

        $withoutPhoto = \App\Models\Product::query()->doesntHave('media')->count();
        $this->line('  products without a photo: '.$withoutPhoto);
        if ($withoutPhoto) {
            $notes[] = $withoutPhoto.' product(s) have no photo, so they show an empty square. Add photos in the admin, or for the demo catalogue run "php artisan store:import-design-images" (it downloads them from the internet).';
        }

        $this->newLine();
        if ($problems) {
            $this->error('Images are broken. What to fix:');
            foreach ($problems as $i => $problem) {
                $this->line('  '.($i + 1).'. '.$problem);
            }
            $this->newLine();
        } else {
            $this->info('Images are working.');
        }

        if ($notes) {
            $this->line('<comment>Worth fixing (images work either way):</comment>');
            foreach ($notes as $i => $note) {
                $this->line('  '.($i + 1).'. '.$note);
            }
        }

        if (! $problems && ! $notes) {
            $this->line('If images still do not show, open the page, press F12 → Console, and check what the browser reports.');
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
