<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Product;
use App\Services\Media\MediaService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Swap the images of the Demo Store demo products for the illustrated set, without
 * touching anything else (orders, prices, stock and your own products are left alone).
 */
class RefreshDemoImages extends Command
{
    protected $signature = 'store:demo-images';

    protected $description = 'Replace the demo products’ images with the illustrated demo photos';

    public function handle(CatalogSeeder $seeder, MediaService $media): int
    {
        if (! \App\Services\Media\ImageProcessor::isSupported()) {
            $this->error('PHP’s image extension (GD with WebP) is switched off, so images can’t be processed.');
            $this->line('');
            $this->line('To switch it on:');
            $this->line('  1. Run  php --ini  to find your php.ini file (with XAMPP: C:\\xampp\\php\\php.ini).');
            $this->line('  2. Open it, find the line  ;extension=gd  and remove the ; at the start. Save.');
            $this->line('  3. Check:  php -r "var_dump(function_exists(\'imagewebp\'));"  should print bool(true).');
            $this->line('  4. Restart Apache (XAMPP Control Panel), then run this command again.');
            $this->line('');
            $this->line('Nothing was changed.');

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->confirm('This is a live store. Replace the demo products’ images anyway?')) {
            return self::FAILURE;
        }

        $done = 0;
        $replaced = collect();

        foreach ($seeder->definitions() as $p) {
            $product = Product::query()->where('slug', str($p['name']['en'])->slug())
                ->whereHas('variants', fn ($v) => $v->where('sku', 'like', $p['sku'].'%'))
                ->with(['media', 'variants.optionValues', 'options.values'])->first();

            if (! $product) {
                continue;
            }

            $old = $product->media;
            $ids = $seeder->storeImages($media, $p);

            DB::transaction(function () use ($product, $ids, $p) {
                $product->media()->sync(collect($ids)->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

                // Point colour/material variants at their matching photo.
                if (isset($p['image_option']) && ($option = $product->options->get($p['image_option']))) {
                    $valueIndex = $option->values->pluck('id')->flip();
                    foreach ($product->variants as $variant) {
                        $value = $variant->optionValues->firstWhere('product_option_id', $option->id);
                        $variant->forceFill(['media_id' => $value ? ($ids[$valueIndex[$value->id]] ?? null) : null])->save();
                    }
                }
            });

            $replaced = $replaced->merge($old);

            $this->line("  {$product->name}: ".count($ids).' image(s)');
            $done++;
        }

        $seeder->assignCategoryImages();

        // Old images are deleted only once nothing (product, category, banner) uses them any more.
        foreach ($replaced->unique('id') as $image) {
            if (! $image->products()->exists() && ! DB::table('categories')->where('image_id', $image->id)->exists() && ! DB::table('banners')->where('image_id', $image->id)->exists()) {
                $media->delete($image);
            }
        }

        $this->info("Updated {$done} demo product(s) and the demo categories’ images.");

        return self::SUCCESS;
    }
}
