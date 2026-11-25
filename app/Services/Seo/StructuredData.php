<?php

namespace App\Services\Seo;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * schema.org data (JSON-LD) so Google can show prices, stock and star ratings
 * in search results. https://developers.google.com/search/docs/appearance/structured-data
 */
class StructuredData
{
    public function product(Product $product, ?Category $category = null): array
    {
        $url = store_route('products.show', ['slug' => $product->slug]);
        $currency = config('store.base_currency');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $url,
            'image' => $product->media->map(fn ($m) => $m->absoluteUrl('large'))->values()->all(),
            'description' => \Illuminate\Support\Str::limit(\App\Support\RichText::text($product->short_description ?: $product->description), 5000),
            'brand' => ['@type' => 'Brand', 'name' => $product->brand ?: (settings()->trans('general.store_name') ?? config('app.name'))],
            'offers' => $product->variants->map(fn (ProductVariant $v) => array_filter([
                '@type' => 'Offer',
                'sku' => $v->sku,
                'name' => $v->title() ?: null,
                'url' => $url.($product->options->isNotEmpty() ? '?variant='.$v->id : ''),
                'price' => $v->price->toDecimal(),       // always the real AED price, never a converted one
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/'.($v->isInStock() ? 'InStock' : 'OutOfStock'),
                'itemCondition' => 'https://schema.org/NewCondition',
            ]))->values()->all(),
        ];

        if ($sku = $product->variants->first()?->sku) {
            $data['sku'] = $sku;
        }

        if ($category) {
            $data['category'] = $category->name;
        }

        if ($product->rating_count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->rating_average,
                'reviewCount' => $product->rating_count,
                'bestRating' => '5',
            ];
        }

        return array_filter($data, fn ($v) => $v !== [] && $v !== null && $v !== '');
    }

    /** @param  list<array{label: string, url?: string}>  $items  */
    public function breadcrumbs(array $items): array
    {
        $list = [['label' => __('store.nav.home'), 'url' => store_route('home')], ...$items];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($list)->values()->map(fn ($item, $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $item['url'] ?? url()->current(),
            ]))->all(),
        ];
    }

    /** Store identity + sitelinks search box, for the home page. */
    public function website(): array
    {
        $s = settings();
        $name = $s->trans('general.store_name') ?? config('app.name');
        $home = store_route('home');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => $home.'#organization',
                    'name' => $name,
                    'url' => $home,
                    'email' => $s->get('general.store_email'),
                    'telephone' => $s->get('general.store_phone'),
                    'logo' => $s->get('general.logo') ? \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($s->get('general.logo')) : null,
                    'sameAs' => array_values(array_filter([$s->get('general.instagram_url'), $s->get('general.facebook_url'), $s->get('general.tiktok_url'), $s->get('general.x_url')])),
                ]),
                [
                    '@type' => 'WebSite',
                    'name' => $name,
                    'url' => $home,
                    'inLanguage' => app()->getLocale(),
                    'publisher' => ['@id' => $home.'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => store_route('search').'?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    /** Safe to print inside <script type="application/ld+json">. */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}
