<?php

namespace Tests\Feature\AdminUi;

use App\Models\Admin;
use App\Models\AdminView;
use App\Models\Order;
use App\Models\User;
use Tests\Feature\Checkout\CheckoutTestCase;

class AdminFrameTest extends CheckoutTestCase
{
    private function order(array $data = [], string $price = '100.00'): Order
    {
        $this->cartToken = null;
        $this->addToBag($this->makeProduct('Item '.uniqid(), [], [['price' => $price]])->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData($data));

        return Order::latest('id')->first();
    }

    public function test_frame_has_search_sidebar_and_contextual_save_bar(): void
    {
        $this->actingAs(Admin::factory()->create(['name' => 'Layla Hassan']), 'admin');

        $home = $this->get('/admin')->assertOk();
        $home->assertSee('Good ', false)->assertSee('Layla')->assertSee('adminSearch(', false)->assertSee('Sales channels')->assertSee('Online Store');

        $this->get('/admin/settings/general')->assertSee('Unsaved changes')->assertSee('Discard');
        $this->get('/admin/products/create')->assertSee("dirty || true", false);   // shown from the start on "new" pages
    }

    public function test_search_finds_orders_products_and_customers_within_the_role(): void
    {
        $order = $this->order(['name' => 'Mariam Al Nuaimi']);
        $this->makeProduct('Brass Dallah', [], [['sku' => 'DAL-42']]);
        User::factory()->create(['name' => 'Omar Khalid', 'email' => 'omar@example.com']);

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->getJson('/admin/search?q='.$order->number)->assertJsonPath('groups.0.items.0.title', $order->number);
        $this->getJson('/admin/search?q=mariam')->assertJsonPath('groups.0.items.0.url', route('admin.orders.show', $order));
        $this->getJson('/admin/search?q=dal-42')->assertJsonPath('groups.0.items.0.title', 'Brass Dallah');
        $this->getJson('/admin/search?q=omar@')->assertJsonPath('groups.0.items.0.title', 'Omar Khalid');
        $this->getJson('/admin/search?q=a')->assertJson(['groups' => []]);

        // A Content admin can't see orders or customers in search.
        $this->actingAs(Admin::factory()->create(['role' => 'content']), 'admin');
        $groups = $this->getJson('/admin/search?q=mariam')->json('groups');
        $this->assertSame([], $groups);
        $this->assertSame('Brass Dallah', $this->getJson('/admin/search?q=dallah')->json('groups.0.items.0.title'));
    }

    public function test_order_filters_sorts_and_bulk_fulfil(): void
    {
        $cod = $this->order(['name' => 'Cash Buyer'], '300.00');
        $small = $this->order(['name' => 'Small Buyer'], '50.00');
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->get('/admin/orders?payment=pending')->assertSee($cod->number)->assertSee('Payment status: ', false);
        $this->get('/admin/orders?payment=paid')->assertDontSee($cod->number);
        $this->get('/admin/orders?method=card')->assertDontSee($cod->number);
        $this->get('/admin/orders?date=today')->assertSee($cod->number);
        $this->get('/admin/orders?payment=bogus')->assertSessionHasErrors('payment');

        $html = $this->get('/admin/orders?sort=total_asc')->getContent();
        $this->assertLessThan(strpos($html, $cod->number), strpos($html, $small->number));

        $this->post('/admin/orders/bulk', ['ids' => [$cod->id, $small->id], 'action' => 'fulfill'])->assertSessionHas('success', '2 orders marked as fulfilled');
        $this->assertSame('shipped', $cod->fresh()->fulfillment_status->value);
        $this->get('/admin/orders?fulfillment=unfulfilled')->assertDontSee($cod->number);
    }

    public function test_saved_views_become_tabs(): void
    {
        $this->order(['name' => 'Cash Buyer']);
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->get('/admin/orders?payment=pending&method=cod')->assertSee('Save view');
        $this->post('/admin/views', ['resource' => 'orders', 'name' => 'COD to collect', 'query' => 'payment=pending&method=cod&page=3'])
            ->assertRedirect()->assertSessionHas('success');

        $view = AdminView::sole();
        $this->assertSame(['method' => 'cod', 'payment' => 'pending'], $view->query);   // page dropped, keys sorted
        $this->get('/admin/orders')->assertSee('COD to collect');
        $this->get('/admin/orders?method=cod&payment=pending&view='.$view->id)->assertSee('Delete this view')->assertDontSee('Save view');

        // Another role can't create views for lists it can't open.
        $this->actingAs(Admin::factory()->create(['role' => 'content']), 'admin');
        $this->post('/admin/views', ['resource' => 'orders', 'name' => 'X', 'query' => 'payment=paid'])->assertForbidden();

        $this->actingAs($admin, 'admin')->delete('/admin/views/'.$view->id)->assertRedirect('/admin/orders');
        $this->assertSame(0, AdminView::count());
    }

    public function test_product_and_customer_lists(): void
    {
        $this->makeProduct('Out Candle', [], [['stock_quantity' => 0]]);
        $this->makeProduct('Full Candle', [], [['stock_quantity' => 50]]);
        $this->makeProduct('Draft Candle', ['status' => 'draft'], [['stock_quantity' => 3]]);
        User::factory()->create(['name' => 'Blocked Person', 'is_active' => false]);
        User::factory()->create(['name' => 'Active Person']);

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/products?stock=out')->assertSee('Out Candle')->assertDontSee('Full Candle');
        $this->get('/admin/products?stock=low')->assertSee('Draft Candle')->assertDontSee('Full Candle');
        $this->get('/admin/products?status=draft')->assertSee('Draft Candle')->assertDontSee('Out Candle');
        $this->get('/admin/products')->assertSee('50 in stock');

        $this->get('/admin/customers?segment=blocked')->assertSee('Blocked Person')->assertDontSee('Active Person');
        $this->get('/admin/customers?sort=recent')->assertOk();
    }

    public function test_things_to_do_on_home(): void
    {
        $this->order();
        \App\Models\ProductReview::create(['product_id' => \App\Models\Product::first()->id, 'name' => 'A', 'rating' => 5, 'body' => 'Great piece.', 'status' => 'pending', 'locale' => 'en']);

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin')->assertSee('1 order to fulfill')->assertSee('1 review to moderate')->assertSee('Recent orders');

        $this->actingAs(Admin::factory()->create(['role' => 'content']), 'admin');
        $this->get('/admin')->assertDontSee('order to fulfill')->assertSee('1 review to moderate')->assertDontSee('Total sales');
    }
}
