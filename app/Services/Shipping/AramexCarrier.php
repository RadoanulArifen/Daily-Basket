<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Aramex live rates (Rate Calculator API, JSON). Account details come from the admin
 * (Settings → Shipping and delivery) or .env. Shown at checkout only after a successful
 * "Test connection" and when switched on. If Aramex doesn't answer, the other delivery
 * options still show — customers never see an error.
 */
class AramexCarrier implements ShippingCarrier
{
    private const LIVE = 'https://ws.aramex.net/ShippingAPI.V2/RateCalculator/Service_1_0.svc/json/CalculateRate';

    private const TEST = 'https://ws.dev.aramex.net/ShippingAPI.V2/RateCalculator/Service_1_0.svc/json/CalculateRate';

    /** Creating a shipment and getting its waybill back. */
    private const CREATE_LIVE = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreateShipments';

    private const CREATE_TEST = 'https://ws.dev.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreateShipments';

    /** Printing a label for a waybill that already exists. */
    private const LABEL_LIVE = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/PrintLabel';

    private const LABEL_TEST = 'https://ws.dev.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json/PrintLabel';

    /** Our emirate codes → Aramex city names. */
    public const CITIES = [
        'abu_dhabi' => 'Abu Dhabi', 'dubai' => 'Dubai', 'sharjah' => 'Sharjah', 'ajman' => 'Ajman',
        'umm_al_quwain' => 'Umm Al Quwain', 'ras_al_khaimah' => 'Ras Al Khaimah', 'fujairah' => 'Fujairah',
    ];

    public function isConfigured(): bool
    {
        foreach (['username', 'password', 'account_number', 'account_pin', 'account_entity'] as $field) {
            if (blank(config("services.aramex.{$field}"))) {
                return false;
            }
        }

        return true;
    }

    public function isEnabled(): bool
    {
        return $this->isConfigured() && settings()->bool('shipping.aramex_enabled') && filled(settings()->get('shipping.aramex_tested_at'));
    }

    public function isTestMode(): bool
    {
        return settings()->get('shipping.aramex_mode', 'test') !== 'live';
    }

    public function rates(Money $subtotal, string $emirate, int $weightGrams): array
    {
        if (! isset(self::CITIES[$emirate])) {
            return [];   // Aramex here is domestic UAE delivery: $emirate is an emirate code, or another country's code
        }
        $kg = max(0.5, ceil(max($weightGrams, 1) / 500) / 2);   // rounded up to the next 0.5 kg
        $key = 'aramex:'.($this->isTestMode() ? 't' : 'l').":{$emirate}:{$kg}";

        try {
            $price = Cache::remember($key, 1800, fn () => $this->quote($emirate, $kg)->toDecimal());
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return [new ShippingRate(
            code: 'aramex',
            name: ['en' => 'Aramex courier', 'ar' => 'أرامكس'],
            price: Money::of($price, $subtotal->currency),
            description: ['en' => settings()->trans('shipping.aramex_eta', 'en') ?: '1–2 working days', 'ar' => settings()->trans('shipping.aramex_eta', 'ar') ?: '1–2 أيام عمل'],
        )];
    }

    /** Ask Aramex for one price. Also used by "Test connection". */
    public function quote(string $emirate, float $kg): Money
    {
        $response = Http::timeout(12)->acceptJson()->post($this->isTestMode() ? self::TEST : self::LIVE, [
            'ClientInfo' => [
                'UserName' => config('services.aramex.username'),
                'Password' => config('services.aramex.password'),
                'Version' => 'v1.0',
                'AccountNumber' => config('services.aramex.account_number'),
                'AccountPin' => config('services.aramex.account_pin'),
                'AccountEntity' => config('services.aramex.account_entity'),
                'AccountCountryCode' => config('services.aramex.account_country') ?: 'AE',
                'Source' => 24,
            ],
            'Transaction' => ['Reference1' => '', 'Reference2' => '', 'Reference3' => '', 'Reference4' => '', 'Reference5' => ''],
            'OriginAddress' => ['City' => settings()->get('shipping.aramex_origin_city', 'Dubai'), 'CountryCode' => 'AE'],
            'DestinationAddress' => ['City' => self::CITIES[$emirate] ?? 'Dubai', 'CountryCode' => 'AE'],
            'ShipmentDetails' => [
                'PaymentType' => 'P',
                'ProductGroup' => 'DOM',
                'ProductType' => settings()->get('shipping.aramex_product_type', 'ONP'),
                'ActualWeight' => ['Unit' => 'KG', 'Value' => $kg],
                'ChargeableWeight' => ['Unit' => 'KG', 'Value' => $kg],
                'NumberOfPieces' => 1,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Aramex didn’t answer (HTTP '.$response->status().').');
        }
        if ($response->json('HasErrors')) {
            $notes = collect($response->json('Notifications') ?? [])->map(fn ($n) => trim(($n['Code'] ?? '').' '.($n['Message'] ?? '')))->filter()->implode('; ');
            throw new RuntimeException($notes ?: 'Aramex returned an error.');
        }

        $value = $response->json('TotalAmount.Value');
        if (! is_numeric($value)) {
            throw new RuntimeException('Aramex didn’t return a price.');
        }

        return Money::of(number_format((float) $value, 2, '.', ''), 'AED');
    }

    /**
     * Book a shipment for an order and return its waybill number and label.
     *
     * The address comes from the order, the weight from what is being sent. Aramex answers
     * with a waybill number and a URL to a printable label, both of which are stored on the
     * fulfilment so nobody has to log into Aramex to reprint it.
     *
     * @return array{waybill: string, label_url: ?string}
     */
    public function createShipment(Order $order, float $kg, int $pieces = 1, ?string $note = null): array
    {
        $shop = settings();
        $origin = $shop->get('shipping.aramex_origin_city', 'Dubai');

        $response = Http::timeout(25)->acceptJson()->post($this->isTestMode() ? self::CREATE_TEST : self::CREATE_LIVE, [
            'ClientInfo' => $this->clientInfo(),
            'Transaction' => ['Reference1' => $order->number],
            'Shipments' => [[
                'Reference1' => $order->number,
                'Shipper' => [
                    'Reference1' => $order->number,
                    'AccountNumber' => config('services.aramex.account_number'),
                    'PartyAddress' => [
                        'Line1' => (string) $shop->trans('general.address'),
                        'City' => $origin,
                        'CountryCode' => config('services.aramex.account_country') ?: 'AE',
                    ],
                    'Contact' => [
                        'PersonName' => (string) ($shop->trans('general.store_name') ?? config('app.name')),
                        'CompanyName' => (string) ($shop->trans('general.store_name') ?? config('app.name')),
                        'PhoneNumber1' => (string) $shop->get('general.store_phone'),
                        'EmailAddress' => (string) $shop->get('general.store_email'),
                    ],
                ],
                'Consignee' => [
                    'PartyAddress' => [
                        'Line1' => $order->ship_address_line,
                        'Line2' => (string) $order->ship_building,
                        'City' => self::CITIES[$order->ship_emirate] ?? ucfirst((string) $order->ship_city),
                        'CountryCode' => strtoupper((string) ($order->ship_country ?: 'AE')),
                    ],
                    'Contact' => [
                        'PersonName' => $order->ship_name,
                        'PhoneNumber1' => $order->ship_phone ?: $order->phone,
                        'CellPhone' => $order->ship_phone ?: $order->phone,
                        'EmailAddress' => $order->email,
                    ],
                ],
                'ShippingDateTime' => '/Date('.(now()->getTimestampMs()).')/',
                'DueDate' => '/Date('.(now()->addDay()->getTimestampMs()).')/',
                'Comments' => $note ?: '',
                'PaymentType' => 'P',
                'Details' => [
                    'ActualWeight' => ['Unit' => 'KG', 'Value' => $kg],
                    'ChargeableWeight' => ['Unit' => 'KG', 'Value' => $kg],
                    'NumberOfPieces' => max(1, $pieces),
                    'ProductGroup' => 'DOM',
                    'ProductType' => $shop->get('shipping.aramex_product_type', 'ONP'),
                    'DescriptionOfGoods' => __('admin.shipping.goods_description'),
                    'GoodsOriginCountry' => 'AE',
                    // Cash on delivery: Aramex collects the money for us.
                    'CashOnDeliveryAmount' => $order->payment_method === 'cod'
                        ? ['CurrencyCode' => $order->currency, 'Value' => (float) $order->grand_total->toDecimal()]
                        : ['CurrencyCode' => $order->currency, 'Value' => 0],
                    'Services' => $order->payment_method === 'cod' ? 'CODS' : '',
                ],
            ]],
            'LabelInfo' => ['ReportID' => 9201, 'ReportType' => 'URL'],
        ]);

        $this->guard($response);

        $shipment = $response->json('Shipments.0') ?? [];
        $waybill = (string) ($shipment['ID'] ?? '');

        if ($waybill === '') {
            throw new RuntimeException('Aramex created no waybill for this order.');
        }

        return [
            'waybill' => $waybill,
            'label_url' => $shipment['ShipmentLabel']['LabelURL'] ?? null,
        ];
    }

    /** Fetch a printable label for a waybill booked earlier. */
    public function labelFor(string $waybill): ?string
    {
        $response = Http::timeout(20)->acceptJson()->post($this->isTestMode() ? self::LABEL_TEST : self::LABEL_LIVE, [
            'ClientInfo' => $this->clientInfo(),
            'Transaction' => ['Reference1' => $waybill],
            'ShipmentNumber' => $waybill,
            'LabelInfo' => ['ReportID' => 9201, 'ReportType' => 'URL'],
        ]);

        $this->guard($response);

        return $response->json('ShipmentLabel.LabelURL');
    }

    /** @return array<string, mixed> */
    private function clientInfo(): array
    {
        return [
            'UserName' => config('services.aramex.username'),
            'Password' => config('services.aramex.password'),
            'Version' => 'v1.0',
            'AccountNumber' => config('services.aramex.account_number'),
            'AccountPin' => config('services.aramex.account_pin'),
            'AccountEntity' => config('services.aramex.account_entity'),
            'AccountCountryCode' => config('services.aramex.account_country') ?: 'AE',
            'Source' => 24,
        ];
    }

    /** Aramex answers 200 with the error inside, so both have to be checked. */
    private function guard(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException('Aramex didn’t answer (HTTP '.$response->status().').');
        }

        if ($response->json('HasErrors')) {
            $notes = collect($response->json('Notifications') ?? [])
                ->map(fn ($n) => trim(($n['Code'] ?? '').' '.($n['Message'] ?? '')))
                ->filter()->implode('; ');

            throw new RuntimeException($notes ?: 'Aramex returned an error.');
        }
    }
}
