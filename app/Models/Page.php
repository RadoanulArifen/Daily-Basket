<?php

namespace App\Models;

use App\Models\Concerns\HasRichText;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use Auditable, HasRichText, HasTranslations;

    public array $translatable = ['title', 'eyebrow', 'heading', 'intro', 'body', 'footnote', 'seo_title', 'seo_description'];

    /** Written in the admin's HTML editor; cleaned on save (HasRichText). */
    protected array $richText = ['body'];

    /** Page text written before the HTML editor was Markdown. */
    protected bool $richTextMarkdown = true;

    protected $fillable = ['slug', 'title', 'eyebrow', 'heading', 'intro', 'body', 'footnote', 'image_ids', 'seo_title', 'seo_description', 'show_contact_form', 'is_active'];

    protected $attributes = [
        'eyebrow' => null,
        'heading' => null,
        'intro' => null,
        'body' => null,
        'footnote' => null,
        'image_ids' => null,
        'seo_title' => null,
        'seo_description' => null,
        'show_contact_form' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return ['show_contact_form' => 'boolean', 'is_active' => 'boolean', 'image_ids' => 'array'];
    }

    /** Body as safe HTML. Written in the HTML editor; older Markdown text is converted. */
    public function html(): string
    {
        $body = (string) $this->body;

        return \App\Support\RichText::isHtml($body)
            ? \App\Support\RichText::clean($body)
            : \App\Support\RichText::clean(Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }

    /** The page's pictures (About: hero, then the two side-by-side), in the chosen order. */
    public function images(): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter((array) $this->image_ids));
        if ($ids === []) {
            return collect();
        }
        $media = Media::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $media->get($id))->filter()->values();
    }
}
