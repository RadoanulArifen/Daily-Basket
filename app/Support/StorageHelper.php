<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** File clean-up shared by admin controllers that let staff replace an uploaded image. */
final class StorageHelper
{
    /**
     * Deletes a file the admin uploaded earlier. Theme images (paths under "themes/", shipped
     * with the design) and empty paths are never touched.
     */
    public static function deleteUploadedFile(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'themes/')) {
            return;
        }
        Storage::disk(config('store.uploads.disk'))->delete($path);
    }
}
