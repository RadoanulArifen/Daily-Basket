<?php

namespace App\Themes\Freshnest;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\FreshnestSeeder;
use Illuminate\Support\Str;

/**
 * The FreshNest design's photos (hosted on Unsplash), for `php artisan store:import-design-images`.
 * Products and categories that still show the design's placeholder drawing get the real photo;
 * anything the owner has uploaded themselves is left alone. Safe to run again.
 */
class DesignImages
{
    /** @param  callable(string $url, string $name, string $alt): ?int  $download */
    public function run(callable $download, callable $info): void
    {
        $data = FreshnestSeeder::data();
        $unsplash = fn (string $id, int $w) => 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w='.$w.'&q=80';
        $isPlaceholder = fn ($media) => $media === null || str_starts_with((string) $media->original_name, FreshnestSeeder::PLACEHOLDER_PREFIX);

        $info('Products…');
        foreach ($data['PRODUCTS'] as $p) {
            $product = Product::query()->where('slug', Str::slug($p['name']))->with('media')->first();
            if ($product && $isPlaceholder($product->media->first())
                && ($id = $download($unsplash($p['photo'], 1000), $product->slug.'.jpg', $p['name']))) {
                $product->media()->sync([$id => ['position' => 0]]);
            }
        }

        $info('Categories…');
        foreach ($data['CATEGORIES'] as $c) {
            $category = Category::query()->where('slug', $c['slug'])->with('image')->first();
            if ($category && $isPlaceholder($category->image)
                && ($id = $download($unsplash($c['photo'], 800), $c['slug'].'.jpg', $c['name']))) {
                $category->forceFill(['image_id' => $id])->save();
            }
        }

        $info('Homepage banners…');
        $seen = [];
        foreach ($data['BANNERS'] as $b) {
            $position = $seen[$b['placement']] = ($seen[$b['placement']] ?? -1) + 1;
            if (empty($b['photo'])) {
                continue;
            }
            $banner = \App\Models\Banner::query()->where('placement', $b['placement'])->orderBy('sort_order')->orderBy('id')->skip($position)->first();
            if ($banner && ! $banner->image_id && ($id = $download($unsplash($b['photo'], 1200), $b['placement'].'-'.($position + 1).'.jpg', $b['title']))) {
                $banner->forceFill(['image_id' => $id])->save();
            }
        }
    }
}
