<?php

namespace Tests\Feature\AdminUi;

use App\Models\Admin;
use App\Models\Page;
use App\Support\RichText;
use Tests\Feature\Storefront\StorefrontTestCase;

/** The admin's HTML editor (TinyMCE 7, resources/js/admin/tinymce-editor.js). */
class RichEditorTest extends StorefrontTestCase
{
    public function test_rich_fields_are_textareas_with_data_rich_per_language(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $page = Page::query()->create(['title' => ['en' => 'About'], 'slug' => 'about-test', 'body' => ['en' => '<p><strong>Hi</strong> & welcome</p>', 'ar' => '<p>مرحبا</p>'], 'is_active' => true]);

        $html = $this->get("/admin/pages/{$page->id}/edit")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<textarea name="body\[en\]" id="[^"]+_en"[^>]*dir="ltr"[^>]*data-rich/s', $html);
        $this->assertMatchesRegularExpression('/<textarea name="body\[ar\]" id="[^"]+_ar"[^>]*dir="rtl"[^>]*data-rich/s', $html);
        // The HTML travels escaped inside the textarea, so it works even without JavaScript.
        $this->assertStringContainsString('&lt;p&gt;&lt;strong&gt;Hi&lt;/strong&gt; &amp;amp; welcome&lt;/p&gt;</textarea>', $html);
        $this->assertStringContainsString('data-upload-url=', $html);
        $this->assertStringNotContainsString('data-rich-editor', $html);   // the old editor is gone
    }

    public function test_what_tinymce_produces_survives_the_server_cleaner(): void
    {
        $tiny = '<h2 style="text-align: center;">Title</h2><p>A <strong>b</strong> <em>i</em> <u>u</u> <s>s</s> <a href="https://x.test" target="_blank">l</a></p>'
            .'<ul><li>one</li></ul><blockquote><p>q</p></blockquote><hr>'
            .'<p><img src="/storage/media/a.webp" alt="A"></p>'
            .'<p><iframe src="https://www.youtube-nocookie.com/embed/abc123XYZ" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen" loading="lazy"></iframe></p>'
            .'<table><tbody><tr><td style="text-align: right;">c</td></tr></tbody></table>';
        $clean = RichText::clean($tiny);

        foreach (['<h2 style="text-align: center">', '<u>u</u>', '<s>s</s>', 'target="_blank"', '<blockquote>', '<hr>', 'alt="A"', 'youtube-nocookie.com/embed/abc123XYZ', '<td style="text-align: right">c</td>'] as $kept) {
            $this->assertStringContainsString($kept, $clean, "kept: $kept");
        }

        $dirty = '<p onclick="x()">t</p><script>alert(1)</script><p><iframe src="https://evil.test/x"></iframe></p><span style="color:red">r</span>';
        $clean = RichText::clean($dirty);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringNotContainsString('color:red', $clean);
    }

    public function test_the_editor_allows_exactly_the_tags_the_server_keeps(): void
    {
        $js = file_get_contents(resource_path('js/admin/tinymce-editor.js'));
        preg_match('/const VALID_ELEMENTS = \[(.*?)\]\.join/s', $js, $m);
        $this->assertNotEmpty($m, 'VALID_ELEMENTS found');
        preg_match_all("/'([^']+)'/", $m[1], $rules);

        $editor = [];
        foreach ($rules[1] as $rule) {
            preg_match('/^([a-z0-9\/]+)(?:\[(.*)\])?$/', $rule, $r);
            $attrs = array_values(array_diff(isset($r[2]) ? explode('|', $r[2]) : [], ['style']));
            foreach (explode('/', $r[1]) as $tag) {
                $editor[$tag] = $attrs;
            }
        }

        $allowed = (new \ReflectionClassConstant(RichText::class, 'ALLOWED'))->getValue();
        ksort($editor);
        ksort($allowed);
        $this->assertSame(array_keys($allowed), array_keys($editor), 'same tags');
        foreach ($allowed as $tag => $attrs) {
            sort($attrs);
            $mine = $editor[$tag];
            sort($mine);
            $this->assertSame($attrs, $mine, "attributes of <$tag>");
        }
    }

    public function test_no_page_loads_tinymce_from_outside(): void
    {
        $js = file_get_contents(resource_path('js/admin/tinymce-editor.js'));
        $this->assertStringContainsString("license_key: 'gpl'", $js);
        $this->assertStringContainsString("skin_url: 'default'", $js);
        $this->assertStringContainsString("content_css: 'default'", $js);
        $this->assertDoesNotMatchRegularExpression('#https?://[^\'"]*(tiny\.cloud|cdn)#i', $js);
    }
}
