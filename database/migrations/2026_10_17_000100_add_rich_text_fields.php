<?php

use App\Support\RichText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Product page texts per product (with store-wide defaults in Online Store → Preferences), the
 * design's page parts (eyebrow, heading, intro, footnote, pictures) — and every description
 * converted to HTML, now that these fields use the HTML editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('dimensions')->nullable()->after('description');       // Dimensions & Materials
            $table->json('delivery_returns')->nullable()->after('dimensions'); // empty = the store default
            $table->json('trust_badges')->nullable()->after('delivery_returns'); // one per line; empty = the theme's
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->json('eyebrow')->nullable()->after('title');
            $table->json('heading')->nullable()->after('eyebrow');   // shown instead of the title as the big headline
            $table->json('intro')->nullable()->after('heading');
            $table->json('footnote')->nullable()->after('body');
            $table->json('image_ids')->nullable()->after('footnote');
        });

        $this->toHtml('products', ['short_description', 'description']);
        $this->toHtml('categories', ['description']);
        $this->toHtml('brands', ['description']);
        $this->toHtml('pages', ['body'], markdown: true);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['dimensions', 'delivery_returns', 'trust_badges']));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn(['eyebrow', 'heading', 'intro', 'footnote', 'image_ids']));
    }

    /** Plain text (or Markdown for pages) → the HTML the editor shows; existing HTML is only cleaned. */
    private function toHtml(string $table, array $columns, bool $markdown = false): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns, $markdown) {
            foreach ($rows as $row) {
                $update = [];
                foreach ($columns as $column) {
                    $raw = $row->{$column};
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    $values = json_decode($raw, true);
                    if (! is_array($values)) {
                        continue;
                    }
                    $converted = collect($values)->map(function ($value) use ($markdown) {
                        if (! is_string($value) || trim($value) === '') {
                            return $value;
                        }
                        if ($markdown && ! RichText::isHtml($value)) {
                            $value = Str::markdown($value, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
                        }

                        return RichText::clean($value);
                    })->all();
                    if ($converted !== $values) {
                        $update[$column] = json_encode($converted, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($update) {
                    DB::table($table)->where('id', $row->id)->update($update);
                }
            }
        });
    }
};
