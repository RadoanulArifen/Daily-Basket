<?php

namespace App\Http\Controllers\Concerns;

use App\Support\StorageHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Remove-or-replace for image fields stored as a file path on a model (brand logos, …):
 * "remove_<field>" clears it, a new upload replaces it; the old file is deleted either way,
 * theme images never are. Products and categories use the media library instead.
 */
trait HandlesFileUploads
{
    /** @param list<string> $fields */
    protected function handleFileUploads(Model $model, array $data, array $fields, string $folder): void
    {
        foreach ($fields as $field) {
            if (! empty($data["remove_{$field}"])) {
                StorageHelper::deleteUploadedFile($model->{$field});
                $model->{$field} = null;
            }

            if (isset($data[$field])) {
                StorageHelper::deleteUploadedFile($model->{$field});
                $model->{$field} = $data[$field]->store($folder, config('store.uploads.disk', 'public'));
            }
        }
    }
}
