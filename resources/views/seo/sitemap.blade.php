{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach ($codes as $code)
    <url>
        <loc>{{ $localization->url($entry['path'], $code) }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ $entry['lastmod']->toAtomString() }}</lastmod>
@endif
@foreach ($codes as $alt)
        <xhtml:link rel="alternate" hreflang="{{ $alt }}" href="{{ $localization->url($entry['path'], $alt) }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $localization->url($entry['path'], config('store.default_locale')) }}"/>
    </url>
@endforeach
@endforeach
</urlset>
