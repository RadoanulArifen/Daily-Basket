<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Brand create / edit form (admin only — the route group requires auth:admin and the catalog area). */
class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->mayAccess('catalog') ?? false;
    }

    public function rules(): array
    {
        return [
            'name.*' => ['nullable', 'string', 'max:120'],
            'name.en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9-]+$/', Rule::unique('brands', 'slug')->ignore($this->route('brand')?->id)],
            'origin.*' => ['nullable', 'string', 'max:120'],
            'description.*' => ['nullable', 'string', 'max:10000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'logo_small' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_logo_small' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
