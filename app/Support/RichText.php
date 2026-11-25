<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;

/**
 * Text written in the admin's HTML editor.
 *
 * clean()  — on save: keeps only safe formatting (paragraphs, headings, bold, italic, lists,
 *            links, quotes, pictures, YouTube/Vimeo/.mp4 videos, tables, text alignment) and
 *            drops everything else, including scripts, styles, event handlers, javascript:
 *            links and frames from other sites. Plain text becomes paragraphs.
 *            The admin editor (resources/js/admin/tinymce-editor.js) allows exactly these tags.
 * render() — on the storefront: the cleaned HTML, safe to print with {!! !!}.
 * text()   — for snippets, meta tags and search: the words only.
 */
class RichText
{
    /** Tags kept, with the attributes each may keep. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'div' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'del' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'], 'figure' => [], 'figcaption' => [],
        'video' => ['src', 'controls', 'poster', 'width', 'height', 'preload', 'playsinline', 'muted', 'loop'],
        'iframe' => ['src', 'title', 'width', 'height', 'allowfullscreen', 'allow', 'loading', 'frameborder'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'colgroup' => [], 'col' => [],
    ];

    /** Tags that may keep a text-align style (and nothing else in style). */
    private const ALIGNABLE = ['p', 'h2', 'h3', 'h4', 'th', 'td'];

    /** The only sites a video frame may come from. */
    public const EMBEDS = ['https://www.youtube-nocookie.com/embed/', 'https://www.youtube.com/embed/', 'https://player.vimeo.com/video/'];

    /** Removed together with everything inside them. */
    private const DROP = [
        'script', 'style', 'frame', 'frameset', 'object', 'embed', 'applet', 'svg', 'math',
        'form', 'input', 'button', 'textarea', 'select', 'option', 'template', 'noscript', 'link',
        'meta', 'base', 'head', 'title', 'audio', 'source', 'picture', 'canvas',
    ];

    public static function clean(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (! self::isHtml($value)) {
            return self::fromPlain($value);
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="rt-root">'.$value.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('rt-root');
        if (! $root) {
            return self::fromPlain(strip_tags($value));
        }
        self::walk($root);

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        // Trix leaves empty blocks behind; they only add stray gaps.
        $html = preg_replace('#<(p|div|h[2-4])>(\s|&nbsp;|<br>)*</\1>#i', '', $html);

        return trim((string) $html);
    }

    /** Safe HTML for the storefront. Plain text (older content) becomes paragraphs. */
    public static function render(?string $value): HtmlString
    {
        return new HtmlString(self::clean($value));
    }

    /** The words only — for meta descriptions, cards, search and CSV. */
    public static function text(?string $value): string
    {
        $value = preg_replace('#<(br|/p|/div|/h[1-6]|/li)\s*/?>#i', ' ', (string) $value);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public static function isHtml(?string $value): bool
    {
        return (bool) preg_match('#<(p|br|div|h[1-6]|ul|ol|li|strong|b|em|i|u|s|del|a|blockquote|span|pre|code|hr|img|figure|video|iframe|table)\b[^>]*>#i', (string) $value);
    }

    /** Headings and what follows each: [['h' => 'Question', 'html' => '<p>Answer</p>'], …]; 'lead' is the part before the first heading. */
    public static function sections(?string $value): array
    {
        $html = self::clean($value);
        $parts = preg_split('#<h[2-4]\b[^>]*>(.*?)</h[2-4]>#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lead = trim((string) array_shift($parts));
        $sections = [];
        for ($i = 0; $i + 1 < count($parts) + 1; $i += 2) {
            if (! isset($parts[$i])) {
                break;
            }
            $sections[] = ['h' => self::text($parts[$i]), 'html' => trim((string) ($parts[$i + 1] ?? ''))];
        }

        return ['lead' => $lead, 'sections' => $sections];
    }

    private static function fromPlain(string $text): string
    {
        return collect(preg_split('/\R{2,}/', trim($text)))
            ->map(fn ($p) => trim($p))
            ->filter(fn ($p) => $p !== '')
            ->map(fn ($p) => '<p>'.nl2br(e($p), false).'</p>')
            ->implode('');
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            // h1 (a page has one already) and h5/h6 become h2/h4.
            $rename = ['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'][$tag] ?? null;
            if ($rename) {
                $new = $child->ownerDocument->createElement($rename);
                while ($child->firstChild) {
                    $new->appendChild($child->firstChild);
                }
                $node->replaceChild($new, $child);
                $child = $new;
                $tag = $rename;
            }

            if (! array_key_exists($tag, self::ALLOWED)) {
                // Unknown wrapper (span, font, section…): keep what is inside it.
                self::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            // text-align is the only style kept, and only on blocks that can be aligned.
            $align = null;
            if (in_array($tag, self::ALIGNABLE, true) && preg_match('/(?:^|;)\s*text-align\s*:\s*(left|right|center|justify)\s*(?:;|$)/i', (string) $child->getAttribute('style'), $m)) {
                $align = strtolower($m[1]);
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                if (! in_array(strtolower($attribute->name), self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attribute->name);
                }
            }
            if ($align) {
                $child->setAttribute('style', "text-align: {$align}");
            }
            foreach (['width', 'height', 'colspan', 'rowspan'] as $number) {
                if ($child->hasAttribute($number) && ! preg_match('/^\d{1,4}%?$/', trim($child->getAttribute($number)))) {
                    $child->removeAttribute($number);
                }
            }

            if ($tag === 'a') {
                $href = trim((string) $child->getAttribute('href'));
                if ($href === '' || ! preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', $href)) {
                    $child->removeAttribute('href');
                } elseif (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('rel', 'noopener');
                }
                if ($child->hasAttribute('target')) {
                    if ($child->getAttribute('target') === '_blank') {
                        $child->setAttribute('rel', 'noopener noreferrer');
                    } else {
                        $child->removeAttribute('target');
                    }
                }
            }

            // Pictures and videos: this site or https only. Frames: YouTube and Vimeo players only.
            if (in_array($tag, ['img', 'video', 'iframe'], true)) {
                $src = trim((string) $child->getAttribute('src'));
                $ok = $tag === 'iframe'
                    ? collect(self::EMBEDS)->contains(fn ($prefix) => str_starts_with($src, $prefix))
                    : (bool) preg_match('#^(https://|/(?!/))#i', $src);
                if (! $ok) {
                    $node->removeChild($child);

                    continue;
                }
                if ($tag === 'video' && $child->hasAttribute('poster') && ! preg_match('#^(https://|/(?!/))#i', trim($child->getAttribute('poster')))) {
                    $child->removeAttribute('poster');
                }
                if ($tag === 'img' && ! $child->hasAttribute('alt')) {
                    $child->setAttribute('alt', '');
                }
                if ($tag === 'iframe') {
                    $child->setAttribute('loading', 'lazy');
                }
            }

            self::walk($child);
        }
    }
}
