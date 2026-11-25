<?php

use App\Services\Settings\SettingsService;
use Illuminate\Database\Migrations\Migration;

/*
 * The seeded home-page description was one character over the 160 the admin allows, which
 * stopped Store details being saved at all. Trim anything already stored that is too long.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = app(SettingsService::class);
        $value = (array) $settings->get('general.meta_description', []);
        $trimmed = [];

        foreach ($value as $locale => $text) {
            $trimmed[$locale] = mb_strlen((string) $text) > 160
                ? rtrim(mb_substr((string) $text, 0, 157), " \t\n\r,.;—-").'…'
                : $text;
        }

        if ($trimmed !== $value) {
            $settings->setMany('general', ['meta_description' => $trimmed]);
        }
    }

    public function down(): void
    {
        // Nothing to undo: the shortened text stays.
    }
};
