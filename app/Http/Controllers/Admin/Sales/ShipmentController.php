<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\Orders\FulfillmentService;
use App\Services\Shipping\AramexCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Booking a courier shipment for an order, and reprinting its label.
 *
 * Aramex is asked for a waybill; what comes back is stored on the fulfilment, so the order
 * screen can show the tracking number and a link to the label without anyone signing into
 * Aramex.
 */
class ShipmentController extends AdminController
{
    public function store(Request $request, Order $order, AramexCarrier $aramex, FulfillmentService $fulfillments): RedirectResponse
    {
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'pieces' => ['nullable', 'integer', 'min:1', 'max:50'],
            'note' => ['nullable', 'string', 'max:200'],
            'notify' => ['boolean'],
        ]);

        if (! $aramex->isConfigured()) {
            return back()->with('error', __('admin.shipping.aramex_not_configured'));
        }

        try {
            $shipment = $aramex->createShipment($order, (float) $data['weight_kg'], (int) ($data['pieces'] ?? 1), $data['note'] ?? null);
        } catch (\Throwable $e) {
            Log::warning('Aramex: could not create shipment', ['order' => $order->number, 'error' => $e->getMessage()]);

            return back()->with('error', __('admin.shipping.aramex_failed', ['reason' => $e->getMessage()]));
        }

        // Book it as a fulfilment, which is what tells the customer it has shipped.
        $fulfillments->fulfill(
            order: $order,
            quantities: null,               // everything outstanding
            carrier: 'aramex',
            tracking: $shipment['waybill'],
            notify: $request->boolean('notify', true),
            admin: $request->user('admin'),
        );

        $order->fulfillments()->latest('id')->first()?->forceFill(['label_url' => $shipment['label_url']])->save();

        return back()->with('success', __('admin.shipping.aramex_created', ['waybill' => $shipment['waybill']]));
    }

    /** Ask Aramex for the label again — links expire, the waybill does not. */
    public function label(Fulfillment $fulfillment, AramexCarrier $aramex): RedirectResponse
    {
        if (blank($fulfillment->tracking_number)) {
            return back()->with('error', __('admin.shipping.no_waybill'));
        }

        try {
            $url = $aramex->labelFor($fulfillment->tracking_number);
        } catch (\Throwable $e) {
            return back()->with('error', __('admin.shipping.aramex_failed', ['reason' => $e->getMessage()]));
        }

        if (blank($url)) {
            return back()->with('error', __('admin.shipping.no_label'));
        }

        $fulfillment->forceFill(['label_url' => $url])->save();

        return redirect()->away($url);
    }
}
