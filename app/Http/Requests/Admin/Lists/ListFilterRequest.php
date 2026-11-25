<?php

namespace App\Http\Requests\Admin\Lists;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for the filters of admin list pages (query string): every list accepts a search
 * term "q"; lists with a sort menu name their allowed values in sorts(). Each list adds its own
 * filters in filters(). Admin only: the route group requires auth:admin and the list's area.
 */
abstract class ListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /** @return list<string> allowed values of ?sort= (empty = no sort menu) */
    protected function sorts(): array
    {
        return [];
    }

    /** @return array<string, mixed> rules for this list's own filters */
    abstract protected function filters(): array;

    public function rules(): array
    {
        return array_merge(
            ['q' => ['nullable', 'string', 'max:100']],
            $this->sorts() ? ['sort' => ['nullable', Rule::in($this->sorts())]] : [],
            $this->filters(),
        );
    }
}
