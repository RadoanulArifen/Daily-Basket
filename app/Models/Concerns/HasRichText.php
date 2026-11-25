<?php

namespace App\Models\Concerns;

use App\Support\RichText;

/**
 * Cleans the model's HTML-editor fields (listed in $richText) whenever it is saved, from any
 * source — admin forms, CSV import, seeders — so only safe formatting is ever stored.
 */
trait HasRichText
{
    public static function bootHasRichText(): void
    {
        static::saving(function ($model) {
            foreach ($model->richText ?? [] as $attribute) {
                if (! $model->isDirty($attribute)) {
                    continue;
                }

                if (in_array($attribute, $model->translatable ?? [], true)) {
                    $values = collect($model->getTranslations($attribute))
                        ->map(fn ($v) => RichText::clean($model->richTextSource(is_string($v) ? $v : null)))
                        ->filter(fn ($v) => $v !== '')
                        ->all();
                    $model->setTranslations($attribute, $values);
                    if ($values === []) {
                        $model->setAttribute($attribute, null);
                    }
                } else {
                    $clean = RichText::clean($model->richTextSource($model->getAttribute($attribute)));
                    $model->setAttribute($attribute, $clean === '' ? null : $clean);
                }
            }
        });
    }

    /** Models whose older text is Markdown (pages) set $richTextMarkdown: it is converted first. */
    public function richTextSource(?string $value): ?string
    {
        if (($this->richTextMarkdown ?? false) && filled($value) && ! RichText::isHtml($value)) {
            return \Illuminate\Support\Str::markdown($value, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        }

        return $value;
    }
}
