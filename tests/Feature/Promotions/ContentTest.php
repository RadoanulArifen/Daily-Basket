<?php

namespace Tests\Feature\Promotions;

use App\Mail\ContactMessage;
use App\Mail\OrderStatusUpdate;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Checkout\CheckoutTestCase;

class ContentTest extends CheckoutTestCase
{
    public function test_seeded_pages_render_in_both_languages_and_footer_links_work(): void
    {
        $this->seed([ContentSeeder::class, MenuSeeder::class]);

        foreach (['about', 'contact', 'delivery-returns', 'privacy', 'terms'] as $slug) {
            $this->get("/pages/{$slug}")->assertOk();
            $this->get("/ar/pages/{$slug}")->assertOk();
        }
        $this->get('/pages/delivery-returns')->assertSee('<h2>Delivery</h2>', false);
        $this->get('/ar/pages/about')->assertSee('من نحن');
        $this->get('/pages/nope')->assertNotFound();

        Page::where('slug', 'about')->update(['is_active' => false]);
        $this->get('/pages/about')->assertNotFound();
    }

    public function test_raw_html_in_pages_is_escaped(): void
    {
        Page::create(['slug' => 'x', 'title' => ['en' => 'X'], 'body' => ['en' => "Hello <script>alert(1)</script>\n\n[bad](javascript:alert(1))"]]);

        $this->get('/pages/x')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('javascript:alert', false);
    }

    public function test_contact_form_emails_the_store_and_blocks_bots(): void
    {
        Mail::fake();
        $this->seed(ContentSeeder::class);

        $this->post('/pages/contact/contact', ['name' => 'Omar', 'email' => 'omar@example.com', 'message' => 'Do you gift wrap?'])
            ->assertRedirect('/pages/contact')->assertSessionHas('contact_sent');
        Mail::assertQueued(ContactMessage::class, fn ($m) => $m->hasTo('owner@example.com') && $m->hasReplyTo('omar@example.com'));

        $this->post('/pages/contact/contact', ['name' => 'Bot', 'email' => 'b@x.com', 'message' => 'Buy cheap things now', 'website' => 'spam.example']);
        Mail::assertQueuedCount(1);

        $this->post('/pages/about/contact', ['name' => 'Omar', 'email' => 'omar@example.com', 'message' => 'Hello there!'])->assertNotFound();
    }

    public function test_banners_replace_the_home_hero_and_respect_dates(): void
    {
        $this->get('/')->assertSee('Pieces for a home');

        Banner::create(['placement' => 'hero', 'title' => ['en' => 'Eid collection', 'ar' => 'مجموعة العيد'], 'button_label' => ['en' => 'Shop Eid'], 'url' => '/collections/sale']);
        Banner::create(['placement' => 'promo', 'title' => ['en' => 'Future promo'], 'starts_at' => now()->addWeek()]);

        $this->get('/')->assertSee('Eid collection')->assertSee('/collections/sale', false)->assertDontSee('Pieces for a home')->assertDontSee('Future promo');
        $this->get('/ar')->assertSee('مجموعة العيد')->assertSee('/ar/collections/sale', false);
    }

    public function test_admin_content_screens(): void
    {
        $this->seed([ContentSeeder::class, MenuSeeder::class]);
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/pages')->assertOk()->assertSee('Delivery &amp; returns', false);
        $this->post('/admin/pages', ['title' => ['en' => 'Gift wrapping', 'ar' => 'تغليف الهدايا'], 'body' => ['en' => 'We wrap.'], 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->get('/pages/gift-wrapping')->assertOk()->assertSee('We wrap.');

        $this->get('/admin/banners/create')->assertOk();
        $this->post('/admin/banners', ['placement' => 'hero', 'title' => ['en' => 'Hello'], 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->post('/admin/banners', ['placement' => 'hero', 'title' => ['en' => 'Hello'], 'url' => '/shop', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->get('/admin/banners')->assertOk()->assertSee('Showing');

        $menu = Menu::where('handle', 'footer-help')->first();
        $this->get('/admin/menus')->assertOk()->assertSee('Footer — Help');
        $this->get("/admin/menus/{$menu->id}")->assertOk();
        $this->post("/admin/menus/{$menu->id}/items", ['label' => ['en' => 'Gift wrapping'], 'url' => '/pages/gift-wrapping', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('/pages/gift-wrapping', false);

        $item = $menu->items()->latest('id')->first();
        $this->put("/admin/menus/{$menu->id}/items/{$item->id}", ['label' => ['en' => 'Wrapping'], 'url' => '/pages/gift-wrapping', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('/pages/gift-wrapping', false);

        $other = Menu::where('handle', 'header')->first();
        $this->delete("/admin/menus/{$other->id}/items/{$item->id}")->assertNotFound();
        $this->delete("/admin/menus/{$menu->id}/items/{$item->id}")->assertSessionHas('success');
    }

    public function test_status_emails_use_editable_templates_and_can_be_switched_off(): void
    {
        Mail::fake();
        $this->addToBag($this->makeProduct('Throw')->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::sole();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/settings/notifications')->assertOk()->assertSee('Order shipped');
        $this->put('/admin/settings/notifications', [
            'shipped_enabled' => '1', 'delivered_enabled' => '0', 'cancelled_enabled' => '1',
            'shipped_subject' => ['en' => '{name}, order {number} left our Dubai studio'],
        ])->assertSessionHasNoErrors();

        $this->post("/admin/orders/{$order->id}/ship", ['carrier' => 'Aramex', 'tracking_number' => 'AX1']);
        $this->post("/admin/orders/{$order->id}/deliver");

        Mail::assertQueued(OrderStatusUpdate::class, fn ($m) => $m->event === 'shipped'
            && $m->envelope()->subject === "Mariam, order {$order->number} left our Dubai studio");
        Mail::assertNotQueued(OrderStatusUpdate::class, fn ($m) => $m->event === 'delivered');
    }
}
