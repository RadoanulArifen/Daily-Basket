<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Template mistakes that render fine on the server but break in the browser.
 */
class BladeTemplatesTest extends TestCase
{
    public function test_js_directive_is_never_used_inside_a_component_tag(): void
    {
        $offenders = [];
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../resources/views'));

        foreach ($views as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            // @js isn't compiled inside <x-component …> attributes, so Alpine receives the raw text.
            if (preg_match_all('/<x-[a-z0-9.\-]+\b[^>]*?@js\(/s', file_get_contents($file->getPathname()), $m)) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Move @js(...) out of component tags (use a wrapper element or a data- attribute).');
    }

    public function test_alpine_x_show_never_sits_on_an_element_with_an_inline_display(): void
    {
        // x-show removes the element's display style when it runs, wiping e.g. the design's
        // display:flex. Put x-show on a wrapper element instead.
        $offenders = [];
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../resources/themes'));
        foreach ($views as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')
                && preg_match_all('/<[a-z][^>]*\bx-show=[^>]*\bstyle="[^"]*\bdisplay\s*:|<[a-z][^>]*\bstyle="[^"]*\bdisplay\s*:[^"]*"[^>]*\bx-show=/s', file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Move x-show to a wrapper element (it would remove the inline display).');
    }
}
