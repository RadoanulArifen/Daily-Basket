<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\Category;
use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'slug' => $this->filled('slug') ? strtolower(trim($this->input('slug'))) : null,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'image_id' => $this->filled('image_id') ? $this->input('image_id') : null,
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
        ]);
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:100'],
            'name.ar' => ['nullable', 'string', 'max:100'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'seo_title.*' => ['nullable', 'string', 'max:70'],
            'seo_description.*' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique(Category::class)->ignore($category)],
            'parent_id' => [
                'nullable', 'integer', Rule::exists(Category::class, 'id'),
                // A category can't be moved inside itself or its own sub-categories.
                $category ? Rule::notIn($category->descendantIds()) : 'integer',
            ],
            'image_id' => ['nullable', 'integer', Rule::exists(Media::class, 'id')],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('parent_id') || ! $this->input('parent_id')) {
                return;
            }

            $parentLevel = Category::findOrFail($this->input('parent_id'))->level();
            $category = $this->route('category');
            $subtreeHeight = $category ? $this->subtreeHeight($category) : 1;

            if ($parentLevel + $subtreeHeight > Category::MAX_DEPTH) {
                $validator->errors()->add('parent_id', __('admin.categories.too_deep', ['max' => Category::MAX_DEPTH]));
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'name.en' => __('admin.categories.fields.name').' (English)',
            'parent_id' => __('admin.categories.fields.parent'),
            'slug' => __('admin.common.url_handle'),
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.not_in' => __('admin.categories.parent_loop'),
            'slug.regex' => __('admin.common.url_handle_invalid'),
        ];
    }

    /** Levels in this category's own branch (1 if it has no children). */
    private function subtreeHeight(Category $category): int
    {
        $height = 1;
        $frontier = [$category->id];

        while ($frontier = Category::query()->whereIn('parent_id', $frontier)->pluck('id')->all()) {
            $height++;
        }

        return $height;
    }
}
