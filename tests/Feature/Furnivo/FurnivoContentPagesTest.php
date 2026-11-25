<?php

namespace Tests\Feature\Furnivo;

use App\Models\Page;
use App\Services\Settings\SettingsService;

class FurnivoContentPagesTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
        $this->seed(\Database\Seeders\FurnivoContentSeeder::class);
    }

    public function test_a_policy_page_uses_the_design(): void
    {
        $this->get('/pages/delivery')->assertOk()->assertSeeInOrder([
            'Home', 'Delivery', 'Service', 'White-glove delivery', 'Lead times', 'Scheduling',
        ], false);
    }

    public function test_the_faqs_page_is_a_list_of_questions(): void
    {
        $page = $this->get('/pages/faqs')->assertOk();

        $page->assertSee('Help centre')->assertSee('Do your prices include VAT?');
        // Each question is a button that opens its answer.
        $this->assertGreaterThanOrEqual(5, substr_count($page->getContent(), 'aria-expanded'));
        $page->assertSee(__('store.faqs.search'));
    }

    public function test_the_contact_page_shows_the_stores_details_and_a_form(): void
    {
        app(SettingsService::class)->setMany('general', [
            'address' => ['en' => '48 Wigmore Street'],
            'opening_hours' => ['en' => 'Sat–Thu 10:00–20:00'],
        ]);

        $this->get('/pages/contact')->assertOk()
            ->assertSee(__('store.contact.address'))
            ->assertSee('48 Wigmore Street')
            ->assertSee('Sat–Thu 10:00–20:00')
            ->assertSee(__('store.contact.send'))
            ->assertSee('*');   // required marks
    }

    public function test_the_contact_form_sends_a_message(): void
    {
        $this->post('/pages/contact/contact', [
            'name' => 'Layla Ahmed', 'email' => 'layla@example.com', 'phone' => '+971501234567',
            'message' => 'Do you deliver to Abu Dhabi on weekends?',
        ])->assertRedirect();

        $this->followingRedirects()->post('/pages/contact/contact', [
            'name' => 'Layla Ahmed', 'email' => 'layla@example.com',
            'message' => 'Do you deliver to Abu Dhabi on weekends?',
        ])->assertOk()->assertSee(__('store.contact.sent'));
    }

    public function test_a_page_that_is_switched_off_is_not_reachable(): void
    {
        Page::query()->where('slug', 'delivery')->update(['is_active' => false]);

        $this->get('/pages/delivery')->assertNotFound();
    }
}
