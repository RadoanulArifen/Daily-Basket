<?php

namespace App\Console\Commands;

use App\Models\Product;
use Database\Seeders\FurnivoSeeder;
use Illuminate\Console\Command;

/**
 * Give products that have no reviews a set of sample ones, so the storefront looks alive
 * before real customers have written any. Products that already have reviews are left alone.
 */
class DemoReviews extends Command
{
    protected $signature = 'store:demo-reviews {--limit=0 : Only do this many products}';

    protected $description = 'Add sample reviews to products that have none';

    public function handle(): int
    {
        $products = Product::query()->doesntHave('reviews')
            ->when((int) $this->option('limit') > 0, fn ($q) => $q->limit((int) $this->option('limit')))
            ->get();

        if ($products->isEmpty()) {
            $this->info('Every product already has reviews.');

            return self::SUCCESS;
        }

        $seeder = new FurnivoSeeder;

        foreach ($products as $product) {
            $seeder->addDemoReviews($product);
        }

        $this->info($products->count().' product(s) now have sample reviews.');

        return self::SUCCESS;
    }
}
