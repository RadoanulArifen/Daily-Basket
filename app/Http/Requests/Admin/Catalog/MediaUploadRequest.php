<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public const MAX_KB = 10240; // 10 MB — phone photos are large; we shrink them to WebP.

    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_KB, 'dimensions:min_width=50,min_height=50,max_width=8000,max_height=8000'],
            'alt' => ['nullable', 'array'],
            'alt.*' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => __('admin.media.image')];
    }
}
