<?php

namespace Tests\Feature\AdminUi;

use App\Models\Admin;
use App\Models\User;
use Tests\Feature\Storefront\StorefrontTestCase;

class AdminPagesTest extends StorefrontTestCase
{
    public function test_customer_page_notes_are_private_to_admins(): void
    {
        $customer = User::factory()->create(['name' => 'Hessa Rashid', 'password' => 'secret-pass-1']);
        $this->actingAs(Admin::factory()->create(['role' => 'orders']), 'admin');

        $this->get("/admin/customers/{$customer->id}")->assertOk()->assertSee('Customer for')->assertSee('Amount spent')->assertSee('Created an account');
        $this->put("/admin/customers/{$customer->id}/note", ['admin_note' => 'Prefers WhatsApp'])->assertSessionHas('success');
        $this->assertSame('Prefers WhatsApp', $customer->fresh()->admin_note);
        $this->get("/admin/customers/{$customer->id}")->assertSee('Prefers WhatsApp');

        auth('admin')->logout();
        $this->actingAs($customer, 'web')->get('/account')->assertOk()->assertDontSee('Prefers WhatsApp');
    }

    public function test_settings_menu_follows_the_role(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/settings/general')->assertSee('Shipping and delivery')->assertSee('Users and permissions')->assertSee('Your account');

        $this->actingAs(Admin::factory()->create(['role' => 'manager']), 'admin');
        $this->get('/admin/activity')->assertOk()->assertSee('Activity log')->assertSee('Your account')->assertDontSee('Shipping and delivery')->assertDontSee('Users and permissions');

        $this->actingAs(Admin::factory()->create(['role' => 'content']), 'admin');
        $this->get('/admin/account')->assertOk()->assertSee('Your account')->assertDontSee('Activity log');
    }

    public function test_product_page_shows_search_preview_and_view_link(): void
    {
        $product = $this->makeProduct('Brass Dallah');
        $product->update(['seo_description' => ['en' => 'Hand-finished brass coffee pot.']]);
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get("/admin/products/{$product->id}/edit")->assertOk()
            ->assertSee('Hand-finished brass coffee pot.')->assertSee('products › brass-dallah', false)
            ->assertSee('/products/brass-dallah', false)->assertSee('Online Store');
        $this->get('/admin/products/create')->assertOk()->assertSee('editing: true', false);
    }
}
