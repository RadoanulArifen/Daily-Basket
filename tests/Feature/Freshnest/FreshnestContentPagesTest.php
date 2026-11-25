<?php

namespace Tests\Feature\Freshnest;

/** Content pages in the design's layouts, from Content → Pages. */
class FreshnestContentPagesTest extends FreshnestTestCase
{
    public function test_info_pages_show_figures_and_sections(): void
    {
        $this->seedFreshnest();

        $this->get('/pages/delivery-info')->assertOk()
            ->assertSee('How, when and where we deliver', false)
            ->assertSeeInOrder(['45 min', 'Average delivery time', '$50', 'Delivery areas', 'Delivery slots', 'Fees'])
            ->assertDontSee('<ul><li><strong>', false);
        $this->get('/pages/returns-refunds')->assertOk()->assertSee('Quality guarantee');
        $this->get('/pages/privacy-policy')->assertOk()->assertSee('Information we collect');
        $this->get('/pages/careers')->assertOk()->assertSee('Open roles')->assertSee('Delivery Driver');
    }

    public function test_our_growers_lists_the_brands(): void
    {
        $this->seedFreshnest();

        $this->get('/pages/our-growers')->assertOk()
            ->assertSee('How we choose growers')->assertSee('Sunridge Orchards')->assertSee('Sonoma County, CA')->assertSee('Mill Lane Bakehouse');
    }

    public function test_faq_is_an_accordion(): void
    {
        $this->seedFreshnest();

        $this->get('/pages/faq')->assertOk()->assertSee('How fast is delivery?')->assertSee('Which payment methods do you accept?')->assertSee('aria-expanded', false);
    }

    public function test_contact_page_cards_map_and_form(): void
    {
        $this->seedFreshnest();

        $this->get('/pages/contact')->assertOk()
            ->assertSee('Call the store')->assertSee('+1 (800) 555-0132')->assertSee('Email us')->assertSee('Visit the depot')
            ->assertSee('contact-map.png', false)->assertSee('Get directions')
            ->assertSee('Send us a message')->assertSee('name="message"', false);

        $this->post('/pages/contact/contact', ['name' => 'Jamie Rivera', 'email' => 'jamie@example.com', 'message' => '[A product] Do you stock oat milk?'])
            ->assertRedirect()->assertSessionHas('contact_sent');
    }

    public function test_offers_uses_the_sale_listing_with_its_own_headline(): void
    {
        $this->seedFreshnest();

        $this->get('/collections/sale')->assertOk()->assertSee('Offers')->assertSee('New offers land each Thursday', false);
    }

    public function test_offers_page_lists_live_deals_and_codes(): void
    {
        $this->seedFreshnest();

        $this->get('/pages/offers')->assertOk()
            ->assertSee('Weekend Savings Bonanza')->assertSee('30% OFF')->assertSee('Ends in')
            ->assertSee('Buy 1 Get 1')->assertSee('AUTOMATIC')->assertSee('FRESH5')->assertSee('FIRST10')->assertSee('New customer')->assertSee('10% off your first order')->assertSee('$5 off orders over $40', false)->assertSee('Grab the Deal');
        $this->get('/')->assertSee('/pages/offers', false);
    }
}
