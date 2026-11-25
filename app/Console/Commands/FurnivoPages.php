<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\RichText;
use Database\Seeders\FurnivoContentSeeder;
use Illuminate\Console\Command;

/**
 * Brings an existing Furnivo store's pages and product-page texts in line with the design.
 *
 *   php artisan store:furnivo-pages          fills in only what is empty
 *   php artisan store:furnivo-pages --force  resets the design's pages to the design's words
 */
class FurnivoPages extends Command
{
    protected $signature = 'store:furnivo-pages {--force : Replace the words of the design\'s pages (About, Careers, FAQs, Delivery, Terms …) with the design\'s}';

    protected $description = 'Fill the Furnivo pages and product-page texts from the design';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        if ($force && ! $this->confirm('This replaces the text of the design\'s pages, including any changes you made to them. Continue?', true)) {
            return self::SUCCESS;
        }

        $seeder = new FurnivoContentSeeder;
        $pages = $seeder->syncPages(overwrite: $force);
        $seeder->syncProductDefaults(overwrite: false);
        $this->info("Pages updated: {$pages}.");

        // Each product's "Dimensions & Materials", as in the design, where it is still empty.
        $filled = 0;
        Product::query()->whereNull('dimensions')->each(function (Product $product) use (&$filled) {
            $spec = rtrim(RichText::text($product->getTranslation('short_description', 'en', false)), '.');
            if ($spec === '') {
                return;
            }
            $product->setTranslation('dimensions', 'en', '<p>Materials: '.e($spec).'. Made-to-order dimensions available — specify your exact size, finish and configuration via Custom Order.</p>');
            $product->saveQuietly();
            $filled++;
        });
        $this->info("Products given a Dimensions & Materials text: {$filled}.");

        $this->line('About page pictures: php artisan store:import-design-images');
        app(\App\Services\Settings\SettingsService::class)->flush();

        return self::SUCCESS;
    }
}
