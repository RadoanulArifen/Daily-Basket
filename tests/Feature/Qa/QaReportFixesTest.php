<?php

namespace Tests\Feature\Qa;

use App\Models\OrderEvent;
use Illuminate\Support\Facades\File;
use Tests\Feature\Checkout\CheckoutTestCase;

/**
 * Fixes from the QA report of 15 Oct 2026: scripts that a server can block, the order
 * timeline's time, and the fulfil form's starting quantities.
 */
class QaReportFixesTest extends CheckoutTestCase
{
    public function test_no_template_uses_inline_event_handlers_or_unsigned_inline_scripts(): void
    {
        $bad = [];
        foreach ([resource_path('views'), resource_path('themes')] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }
                $html = $file->getContents();
                // onclick=, onchange=, onsubmit= … are refused by the Content-Security-Policy.
                if (preg_match('/<[a-z][^>]*\son[a-z]+\s*=\s*["\']/i', $html, $m)) {
                    $bad[] = $file->getRelativePathname().': '.trim($m[0]);
                }
                // Inline <script> blocks must carry the nonce (JSON data blocks and src= scripts are fine).
                preg_match_all('/<script\b([^>]*)>/i', $html, $tags);
                foreach ($tags[1] as $attrs) {
                    if (! preg_match('/nonce=|\bsrc=|type="application\/(ld\+)?json"|type="text\/x-/i', $attrs)) {
                        $bad[] = $file->getRelativePathname().': <script'.$attrs.'>';
                    }
                }
            }
        }

        $this->assertSame([], $bad);
    }

    public function test_pages_do_not_load_livewires_separate_script(): void
    {
        foreach (['/', '/'.config('store.admin_path').'/login'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('/livewire-', $html, $url);
            $this->assertStringNotContainsString('livewire.js', $html, $url);
            $this->assertStringNotContainsString('livewire.min.js', $html, $url);
        }
        $this->assertFalse(config('livewire.inject_assets'));
    }

    public function test_every_script_entry_point_bundles_alpine(): void
    {
        $entries = ['resources/js/admin.js', 'resources/js/storefront.js', 'resources/themes/freshnest/js/theme.js', 'resources/themes/furnivo/js/theme.js'];
        foreach ($entries as $entry) {
            $this->assertMatchesRegularExpression("#^import '(\./|\.\./\.\./\.\./js/)alpine\.js';#m", file_get_contents(base_path($entry)), $entry);
        }
        $this->assertStringContainsString('livewire.esm.js', file_get_contents(resource_path('js/alpine.js')));
        $this->assertFileExists(base_path('vendor/livewire/livewire/dist/livewire.esm.js'));
    }

    public function test_order_timeline_shows_the_same_placed_time_as_the_header(): void
    {
        $this->addToBag($this->makeProduct('Throw', [], [['price' => '100.00', 'stock_quantity' => 10, 'sku' => 'TH-1']])->variants()->first(), 1);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = \App\Models\Order::query()->sole();
        $order->forceFill(['placed_at' => '2026-10-06 03:47:00'])->save();
        OrderEvent::query()->where('order_id', $order->id)->where('type', 'placed')->update(['created_at' => '2026-10-05 02:22:00', 'message' => 'Order placed']);

        $this->actingAs(\App\Models\Admin::factory()->create(), 'admin');
        $html = $this->get("/admin/orders/{$order->id}")->assertOk()->getContent();
        $at = $order->fresh()->placed_at->timezone(config('store.display_timezone'));

        $this->assertStringContainsString($at->translatedFormat('j F Y \a\t H:i'), $html);
        $this->assertStringContainsString('<time class="text-xs text-muted" datetime="'.$order->fresh()->placed_at->toIso8601String().'">'.$at->translatedFormat('j M, H:i').'</time>', $html);
        $this->assertStringNotContainsString('Order placed<span class="text-ink-soft"> — Order placed', $html);
    }

    public function test_fulfil_form_starts_with_what_is_left_to_send(): void
    {
        $this->addToBag($this->makeProduct('Throw', [], [['price' => '100.00', 'stock_quantity' => 10, 'sku' => 'TH-1']])->variants()->first(), 3);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = \App\Models\Order::with('items')->sole();
        $item = $order->items->first();

        $this->actingAs(\App\Models\Admin::factory()->create(), 'admin');
        $this->get("/admin/orders/{$order->id}/fulfill")->assertOk()
            ->assertSee('name="items['.$item->id.']" value="3"', false);

        // Sent 1 of 3: the form now starts at the 2 that are left.
        $this->post("/admin/orders/{$order->id}/fulfillments", ['items' => [$item->id => 1]]);
        $this->get("/admin/orders/{$order->id}/fulfill")->assertOk()
            ->assertSee('name="items['.$item->id.']" value="2"', false);
    }

    public function test_flash_messages_are_printed_for_pages_without_scripts(): void
    {
        $this->actingAs(\App\Models\Admin::factory()->create(), 'admin');
        $this->withSession(['success' => 'Saved it'])->get('/'.config('store.admin_path'))
            ->assertOk()->assertSee('x-init="$el.remove()"', false)->assertSee('Saved it');
    }
}
