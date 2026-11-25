<?php

namespace App\Services\Media;

use GdImage;
use RuntimeException;

/**
 * Resizes images and converts them to WebP using PHP's built-in GD extension.
 * Re-encoding also strips EXIF data (camera details, GPS location) from uploads.
 */
class ImageProcessor
{
    public function __construct(private int $quality = 82) {}

    public static function isSupported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    /**
     * @return array{0: GdImage, 1: int, 2: int} image, width, height
     */
    public function load(string $path): array
    {
        if (! self::isSupported()) {
            throw new RuntimeException('The PHP GD extension with WebP support is required to process images.');
        }

        $data = @file_get_contents($path);
        $image = $data !== false ? @imagecreatefromstring($data) : false;

        if (! $image instanceof GdImage) {
            throw new RuntimeException('This file could not be read as an image.');
        }

        $image = $this->orient($image, $path);

        // Keep transparency for PNG/WebP logos and cut-out product shots.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return [$image, imagesx($image), imagesy($image)];
    }

    /** Scale down so the longest side is at most $maxSide. Never scales up. */
    public function resize(GdImage $image, int $maxSide): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    public function encodeWebp(GdImage $image): string
    {
        ob_start();
        imagewebp($image, null, $this->quality);

        return (string) ob_get_clean();
    }

    /** Phone photos are often stored sideways with an EXIF "rotate me" flag. */
    private function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
