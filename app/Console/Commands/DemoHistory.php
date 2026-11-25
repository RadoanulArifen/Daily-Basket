<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductReview;
use App\Support\Countries;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A year of believable shop history for demos and screenshots: customers who signed up over the
 * year, ~1,300 orders (more at weekends and in November–December, growing over the year) with
 * real products, payments, fulfilments and deliveries, a few cancellations and refunds, the
 * analytics funnel, newsletter sign-ups, reviews and product dates — so Analytics, the dashboard,
 * the order and customer lists all look like a running shop.
 *
 *   php artisan store:demo-history            # adds a year of history
 *   php artisan store:demo-history --fresh    # removes the history it made before, then adds it again
 *
 * Everything it makes uses @example.net addresses, so --fresh removes exactly that. It refuses to
 * run on a live shop (APP_ENV=production) unless DEMO_MODE=true or --force is given.
 */
class DemoHistory extends Command
{
    protected $signature = 'store:demo-history {--days=365} {--fresh} {--force}';

    protected $description = 'Add a year of demo orders, customers, sales and analytics';

    private const DOMAIN = 'example.net';

    private const FIRST = ['Amelia', 'Noah', 'Sofia', 'Liam', 'Mia', 'Omar', 'Layla', 'Lucas', 'Aisha', 'Ethan', 'Zara', 'Mason', 'Hana', 'Leo', 'Nora', 'Adam', 'Emma', 'Yusuf', 'Chloe', 'Ryan', 'Maya', 'Daniel', 'Sara', 'Jack', 'Lina', 'Owen', 'Ava', 'Karim', 'Ella', 'Sam'];

    private const LAST = ['Carter', 'Haddad', 'Nguyen', 'Rossi', 'Khan', 'Patel', 'Silva', 'Murphy', 'Okafor', 'Schmidt', 'Brooks', 'Mansour', 'Lee', 'Garcia', 'Walker', 'Rahman', 'Bennett', 'Costa', 'Hughes', 'Farouk'];

    private const CITIES = ['US' => [['San Francisco', 'California'], ['Austin', 'Texas'], ['Seattle', 'Washington'], ['Brooklyn', 'New York'], ['Denver', 'Colorado']],
        'AE' => [['Dubai', 'Dubai'], ['Abu Dhabi', 'Abu Dhabi'], ['Sharjah', 'Sharjah']],
        'GB' => [['London', 'England'], ['Manchester', 'England']], 'CA' => [['Toronto', 'Ontario'], ['Vancouver', 'British Columbia']]];

    public function handle(): int
    {
        if (app()->environment('production') && ! config('store.demo') && ! $this->option('force')) {
            $this->components->error('This is a live shop. Use --force only on a demo or test copy.');

            return self::FAILURE;
        }

        $products = Product::query()->where('status', 'active')->with(['variants' => fn ($v) => $v->where('is_active', true)->with('optionValues'), 'media'])->get()
            ->filter(fn ($p) => $p->variants->isNotEmpty())->values();
        if ($products->isEmpty()) {
            $this->components->error('Add products first (php artisan db:seed).');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->removePrevious();
        }

        $days = max(30, (int) $this->option('days'));
        mt_srand(20261004);   // the same history every time
        $start = now()->subDays($days)->startOfDay();

        DB::transaction(function () use ($products, $days, $start) {
            $customers = $this->customers($start, $days);
            $count = $this->orders($products, $customers, $start, $days);
            $this->funnel($start, $days);
            $this->subscribers($start, $days);
            $this->reviews($products, $customers);
            $this->productDates($products, $start);
            $this->components->info("Added {$count} orders and ".count($customers).' customers over '.$days.' days.');
        });

        return self::SUCCESS;
    }

    private function removePrevious(): void
    {
        $orderIds = DB::table('orders')->where('email', 'like', '%@'.self::DOMAIN)->pluck('id');
        foreach ($orderIds->chunk(500) as $chunk) {
            DB::table('orders')->whereIn('id', $chunk)->delete();   // items, payments, events, fulfilments cascade
        }
        DB::table('users')->where('email', 'like', '%@'.self::DOMAIN)->delete();
        DB::table('subscribers')->where('email', 'like', '%@'.self::DOMAIN)->delete();
        DB::table('funnel_counts')->delete();
    }

    /** @return list<array{id: int, name: string, email: string, phone: string, country: string, state: string, city: string}> */
    private function customers(Carbon $start, int $days): array
    {
        $password = Hash::make(Str::random(32));
        $countries = array_values(array_intersect(array_keys(self::CITIES), Countries::allowedCodes())) ?: [Countries::default()];
        $home = Countries::default();
        $out = [];
        for ($i = 1; $i <= 180; $i++) {
            $first = self::FIRST[$i % count(self::FIRST)];
            $last = self::LAST[intdiv($i, count(self::FIRST)) % count(self::LAST)];
            $country = mt_rand(1, 10) <= 8 && isset(self::CITIES[$home]) ? $home : $countries[array_rand($countries)];
            [$city, $state] = (self::CITIES[$country] ?? [['Capital', '']])[mt_rand(0, count(self::CITIES[$country] ?? [1]) - 1)];
            $at = $start->copy()->addDays((int) floor(($i / 180) ** 0.8 * $days))->setTime(mt_rand(8, 22), mt_rand(0, 59));
            $email = strtolower($first.'.'.$last.$i).'@'.self::DOMAIN;
            $phone = '+1415555'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $id = DB::table('users')->insertGetId(['name' => "{$first} {$last}", 'email' => $email, 'phone' => $phone, 'password' => $password,
                'email_verified_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
            DB::table('customer_addresses')->insert(['user_id' => $id, 'label' => 'Home', 'name' => "{$first} {$last}", 'phone' => $phone,
                'country' => $country, 'state' => $state, 'emirate' => Countries::emirate($country, $state), 'city' => $city,
                'address_line' => mt_rand(10, 980).' '.['Oak', 'Maple', 'Cedar', 'Palm', 'Harbor'][mt_rand(0, 4)].' Street', 'building' => null,
                'is_default' => true, 'created_at' => $at, 'updated_at' => $at]);
            $out[] = ['id' => $id, 'name' => "{$first} {$last}", 'email' => $email, 'phone' => $phone, 'country' => $country, 'state' => $state, 'city' => $city, 'since' => $at];
        }

        return $out;
    }

    private function orders($products, array $customers, Carbon $start, int $days): int
    {
        $settings = settings();
        $currency = (string) config('store.base_currency');
        $flat = (float) ($settings->get('shipping.flat_rate') ?: 5);
        $freeOver = $settings->bool('shipping.free_enabled', true) ? (float) ($settings->get('shipping.free_threshold') ?: 0) : 0;
        $shipName = (array) $settings->get('shipping.flat_name', []);
        $code = DB::table('discounts')->where('is_active', true)->value('code');
        $prefix = (string) config('store.order_number_prefix');
        // Translatable JSON column, like real orders: {"en": …, "ar": …}.
        $shipJson = json_encode(array_filter($shipName) ?: ['en' => 'Standard delivery'], JSON_UNESCAPED_UNICODE);
        $count = 0;

        for ($d = 0; $d < $days; $d++) {
            $day = $start->copy()->addDays($d);
            $growth = 0.55 + 0.9 * ($d / $days);
            $season = in_array($day->month, [11, 12], true) ? 1.6 : ($day->month === 1 ? 0.8 : 1.0);
            $weekend = $day->isWeekend() ? 1.3 : 1.0;
            $n = (int) round(3.6 * $growth * $season * $weekend + (mt_rand(-10, 10) / 10));

            for ($k = 0; $k < max(0, $n); $k++) {
                $placed = $day->copy()->setTime(mt_rand(7, 23), mt_rand(0, 59), mt_rand(0, 59));
                if ($placed->isFuture()) {
                    continue;
                }
                $eligible = array_values(array_filter($customers, fn ($c) => $c['since'] <= $placed));
                $customer = $eligible && mt_rand(1, 10) <= 7 ? $eligible[array_rand($eligible)] : null;
                $guestN = mt_rand(1000, 99999);
                $buyer = $customer ?? ['id' => null, 'name' => self::FIRST[mt_rand(0, 29)].' '.self::LAST[mt_rand(0, 19)], 'email' => "guest{$guestN}@".self::DOMAIN,
                    'phone' => '+1415556'.str_pad((string) ($guestN % 10000), 4, '0', STR_PAD_LEFT), 'country' => Countries::default(), 'state' => (self::CITIES[Countries::default()][0][1] ?? ''), 'city' => (self::CITIES[Countries::default()][0][0] ?? 'City')];

                $lines = [];
                $subtotal = 0.0;
                foreach (collect($products)->random(min(mt_rand(1, 4), count($products))) as $product) {
                    $variant = $product->variants->random();
                    $qty = mt_rand(1, 10) <= 7 ? 1 : mt_rand(2, 3);
                    $price = (float) $variant->price->toDecimal();
                    $subtotal += $price * $qty;
                    $title = array_filter(['en' => $variant->title('en'), 'ar' => $variant->title('ar')]);
                    $lines[] = ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'name' => json_encode($product->getTranslations('name'), JSON_UNESCAPED_UNICODE),
                        'variant_title' => $title ? json_encode($title, JSON_UNESCAPED_UNICODE) : null,
                        'sku' => $variant->sku, 'image' => $product->media->first()?->url('thumb'), 'unit_price' => number_format($price, 2, '.', ''),
                        'quantity' => $qty, 'line_total' => number_format($price * $qty, 2, '.', '')];
                }
                $discount = $code && mt_rand(1, 100) <= 15 ? round($subtotal * 0.10, 2) : 0.0;
                $shipping = $freeOver > 0 && $subtotal - $discount >= $freeOver ? 0.0 : $flat;
                $grand = round($subtotal - $discount + $shipping, 2);

                $age = now()->diffInDays($placed, true);
                $card = mt_rand(1, 10) <= 6;
                $roll = mt_rand(1, 100);
                [$status, $payment, $fulfil] = match (true) {
                    $roll <= 4 => ['cancelled', $card ? 'refunded' : 'voided', 'unfulfilled'],
                    $roll <= 7 && $age > 10 => ['completed', 'refunded', 'delivered'],
                    $age > 6 => ['completed', 'paid', 'delivered'],
                    $age > 2 => ['open', $card ? 'paid' : 'pending', 'shipped'],
                    default => ['open', $card ? 'paid' : 'pending', 'unfulfilled'],
                };
                $shipped = in_array($fulfil, ['shipped', 'delivered'], true) ? $placed->copy()->addHours(mt_rand(12, 40)) : null;
                $delivered = $fulfil === 'delivered' ? $shipped->copy()->addHours(mt_rand(20, 70)) : null;
                $paidAt = $payment === 'paid' || $payment === 'refunded' ? ($card ? $placed : ($delivered ?? $placed)) : null;

                $orderId = DB::table('orders')->insertGetId([
                    'access_token' => Str::random(48), 'user_id' => $buyer['id'], 'status' => $status, 'payment_status' => $payment, 'fulfillment_status' => $fulfil,
                    'email' => $buyer['email'], 'phone' => $buyer['phone'], 'locale' => 'en', 'marketing_opt_in' => mt_rand(0, 1),
                    'ship_name' => $buyer['name'], 'ship_phone' => $buyer['phone'], 'ship_country' => $buyer['country'], 'ship_state' => $buyer['state'] ?: null,
                    'ship_emirate' => Countries::emirate($buyer['country'], $buyer['state']), 'ship_city' => $buyer['city'],
                    'ship_address_line' => mt_rand(10, 980).' Market Street', 'currency' => $currency,
                    'subtotal' => number_format($subtotal, 2, '.', ''), 'shipping_total' => number_format($shipping, 2, '.', ''), 'payment_fee' => '0.00',
                    'discount_total' => number_format($discount, 2, '.', ''), 'discount_code' => $discount > 0 ? $code : null, 'promotion_total' => '0.00',
                    'tax_total' => '0.00', 'grand_total' => number_format($grand, 2, '.', ''), 'refunded_total' => $payment === 'refunded' ? number_format($grand, 2, '.', '') : '0.00',
                    'prices_include_tax' => false, 'display_currency' => $currency, 'display_rate' => 1,
                    'shipping_method' => 'standard', 'shipping_method_name' => $shipJson,
                    'payment_method' => $card ? 'stripe' : 'cod', 'placed_at' => $placed, 'paid_at' => $paidAt, 'shipped_at' => $shipped, 'delivered_at' => $delivered,
                    'cancelled_at' => $status === 'cancelled' ? $placed->copy()->addHours(mt_rand(1, 20)) : null, 'created_at' => $placed, 'updated_at' => $delivered ?? $shipped ?? $placed,
                ]);
                DB::table('orders')->where('id', $orderId)->update(['number' => $prefix.(1000 + $orderId)]);

                $itemIds = [];
                foreach ($lines as $line) {
                    $itemIds[] = [DB::table('order_items')->insertGetId($line + ['order_id' => $orderId, 'created_at' => $placed, 'updated_at' => $placed]), $line['quantity']];
                }
                DB::table('payments')->insert(['order_id' => $orderId, 'gateway' => $card ? 'stripe' : 'cod', 'status' => $payment === 'pending' ? 'pending' : ($payment === 'voided' ? 'voided' : ($payment === 'refunded' ? 'refunded' : 'paid')),
                    'amount' => number_format($grand, 2, '.', ''), 'currency' => $currency, 'created_at' => $placed, 'updated_at' => $paidAt ?? $placed]);
                DB::table('order_events')->insert(['order_id' => $orderId, 'type' => 'placed', 'message' => null, 'created_at' => $placed]);
                if ($shipped) {
                    $fid = DB::table('fulfillments')->insertGetId(['order_id' => $orderId, 'sequence' => 1, 'carrier' => 'Local courier', 'tracking_number' => 'TRK'.mt_rand(100000, 999999),
                        'delivered_at' => $delivered, 'created_at' => $shipped, 'updated_at' => $delivered ?? $shipped]);
                    DB::table('fulfillment_items')->insert(array_map(fn ($it) => ['fulfillment_id' => $fid, 'order_item_id' => $it[0], 'quantity' => $it[1]], $itemIds));
                    DB::table('order_events')->insert(['order_id' => $orderId, 'type' => 'shipped', 'message' => null, 'created_at' => $shipped]);
                }
                if ($delivered) {
                    DB::table('order_events')->insert(['order_id' => $orderId, 'type' => 'delivered', 'message' => null, 'created_at' => $delivered]);
                }
                $count++;
            }
        }

        return $count;
    }

    /** Visitors → added to bag → checkout → paid, per day, in proportion to the orders of that day. */
    private function funnel(Carbon $start, int $days): void
    {
        $paid = DB::table('orders')->where('placed_at', '>=', $start)->selectRaw('DATE(placed_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd');
        $rows = [];
        for ($d = 0; $d < $days; $d++) {
            $day = $start->copy()->addDays($d)->toDateString();
            $orders = (int) ($paid[$day] ?? 0);
            $checkout = $orders + mt_rand(1, 4);
            $added = (int) round($checkout * (2.2 + mt_rand(0, 8) / 10));
            $viewed = (int) round($added * (6 + mt_rand(0, 30) / 10));
            foreach (['viewed' => $viewed, 'added' => $added, 'checkout' => $checkout, 'paid' => $orders] as $stage => $n) {
                $rows[] = ['day' => $day, 'stage' => $stage, 'count' => $n, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('funnel_counts')->upsert($chunk, ['day', 'stage'], ['count', 'updated_at']);
        }
    }

    private function subscribers(Carbon $start, int $days): void
    {
        $rows = [];
        for ($i = 1; $i <= 140; $i++) {
            $at = $start->copy()->addDays(mt_rand(0, $days - 1))->setTime(mt_rand(7, 23), mt_rand(0, 59));
            $rows[] = ['email' => "reader{$i}@".self::DOMAIN, 'locale' => 'en', 'created_at' => $at, 'updated_at' => $at];
        }
        DB::table('subscribers')->insertOrIgnore($rows);
    }

    /** A few more approved reviews from customers who bought, then refreshed product ratings. */
    private function reviews($products, array $customers): void
    {
        $texts = ['Great quality, will order again.', 'Arrived quickly and well packed.', 'Exactly as described.', 'Good value for the price.', 'Lovely — my family enjoyed it.', 'Fresh and tasty, five stars.'];
        foreach ($products as $product) {
            for ($r = 0; $r < mt_rand(1, 3); $r++) {
                $c = $customers[array_rand($customers)];
                if (DB::table('product_reviews')->where('product_id', $product->id)->where('user_id', $c['id'])->exists()) {
                    continue;
                }
                $at = Carbon::parse($c['since'])->addDays(mt_rand(5, 60));
                if ($at->isFuture()) {
                    continue;
                }
                DB::table('product_reviews')->insert(['product_id' => $product->id, 'user_id' => $c['id'], 'name' => $c['name'], 'rating' => mt_rand(1, 10) <= 7 ? 5 : 4,
                    'body' => $texts[array_rand($texts)], 'status' => 'approved', 'verified_purchase' => true, 'locale' => 'en', 'created_at' => $at, 'updated_at' => $at]);
            }
            ProductReview::refreshProductRating($product->id);
        }
    }

    /** Products were added over the first months, not all today. */
    private function productDates($products, Carbon $start): void
    {
        foreach ($products as $i => $product) {
            $at = $start->copy()->subDays(30)->addDays($i * 3 % 150)->setTime(10, 0);
            DB::table('products')->where('id', $product->id)->update(['created_at' => $at]);
        }
    }
}
