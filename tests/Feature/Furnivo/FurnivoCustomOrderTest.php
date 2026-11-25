<?php

namespace Tests\Feature\Furnivo;

use App\Models\CustomOrderRequest;

class FurnivoCustomOrderTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_the_enquiry_popup_is_on_every_page(): void
    {
        foreach (['/', '/products/nord-accent', '/collections/seating'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('Made to Order enquiry')
                ->assertSee('customOrder(', false);
        }
    }

    public function test_the_custom_order_buttons_open_it_without_leaving_the_page(): void
    {
        $this->get('/')->assertOk()->assertSee("\$dispatch('open-custom-order')", false);

        // From a product page the piece and its category come with it.
        $this->get('/products/nord-accent')->assertOk()
            ->assertSee("open-custom-order', { product: 'nord-accent', category: 'Seating' }", false);
    }

    public function test_an_enquiry_with_size_and_material_is_recorded(): void
    {
        $this->postJson('/custom-order', [
            'name' => 'Test Client', 'email' => 'client@example.com', 'phone' => '+971501234567',
            'category' => 'Seating', 'dimensions' => '240 x 95 cm', 'material' => 'walnut, bouclé',
            'product' => 'nord-accent',
        ])->assertOk()->assertJson(['sent' => true]);

        $enquiry = CustomOrderRequest::query()->latest('id')->firstOrFail();
        $this->assertSame('Test Client', $enquiry->name);
        $this->assertSame('240 x 95 cm', $enquiry->dimensions);
        $this->assertNotNull($enquiry->product_id, 'The piece it was raised from is recorded.');
        // With no written message, the size and material are recorded as the message.
        $this->assertSame('Dimensions: 240 x 95 cm · Material: walnut, bouclé', $enquiry->message);
    }

    public function test_an_enquiry_needs_something_to_go_on(): void
    {
        $this->postJson('/custom-order', ['name' => 'Test Client', 'email' => 'client@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors('message');

        $this->assertSame(0, CustomOrderRequest::query()->count());
    }

    public function test_the_full_page_still_works_without_javascript(): void
    {
        $this->get('/custom-order')->assertOk();

        $this->post('/custom-order', [
            'name' => 'Test Client', 'email' => 'client@example.com',
            'message' => 'A dining table for a narrow room, about 240cm long.',
        ])->assertRedirect();

        $this->assertSame(1, CustomOrderRequest::query()->count());
    }
}
