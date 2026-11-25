<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function __construct(private ImageProcessor $processor) {}

    public function upload(UploadedFile $file, array $alt = [], string $collection = 'library'): Media
    {
        return $this->storeFromPath($file->getRealPath(), $file->getClientOriginalName(), $alt, $collection);
    }

    /**
     * Store an image in every size as WebP and create its Media record.
     */
    public function storeFromPath(string $path, string $originalName, array $alt = [], string $collection = 'library'): Media
    {
        $disk = config('store.uploads.disk');
        $directory = 'media/'.now()->format('Y/m').'/'.Str::uuid();

        [$image, $width, $height] = $this->processor->load($path);

        $sizes = [];
        $largestBytes = 0;

        foreach (Media::SIZES as $name => $maxSide) {
            // Skip sizes that would be identical to a smaller one already stored.
            if ($sizes && max($width, $height) <= $this->previousLimit($name)) {
                continue;
            }

            $webp = $this->processor->encodeWebp($this->processor->resize($image, $maxSide));
            Storage::disk($disk)->put("{$directory}/{$name}.webp", $webp);

            $sizes[$name] = "{$name}.webp";
            $largestBytes = strlen($webp);
        }

        $scale = min(1, Media::SIZES['large'] / max($width, $height));

        return Media::create([
            'disk' => $disk,
            'collection' => $collection,
            'directory' => $directory,
            'original_name' => Str::limit(basename($originalName), 250, ''),
            'mime_type' => 'image/webp',
            'size' => $largestBytes,
            'width' => (int) round($width * $scale),
            'height' => (int) round($height * $scale),
            'alt' => array_filter($alt),
            'sizes' => $sizes,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->deleteDirectory($media->directory);
        $media->delete();
    }

    private function previousLimit(string $size): int
    {
        $names = array_keys(Media::SIZES);
        $index = array_search($size, $names, true);

        return $index > 0 ? Media::SIZES[$names[$index - 1]] : 0;
    }
}
