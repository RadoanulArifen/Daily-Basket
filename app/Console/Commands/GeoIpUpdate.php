<?php

namespace App\Console\Commands;

use App\Support\Geo\GeoIp;
use App\Support\Geo\MmdbReader;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * php artisan store:geoip-update — downloads DB-IP's free "IP to City Lite" database
 * (CC BY 4.0, "IP data by DB-IP"). Tries this month's file, then last month's. The new file
 * must open and answer a lookup before it replaces the old one.
 */
class GeoIpUpdate extends Command
{
    protected $signature = 'store:geoip-update';

    protected $description = 'Download the free DB-IP City Lite database used for visitor countries and cities';

    public const URL = 'https://download.db-ip.com/free/dbip-city-lite-%s.mmdb.gz';

    public static function schedule(?Schedule $schedule = null): void
    {
        ($schedule ?? app(Schedule::class))->command('store:geoip-update')->monthlyOn(3, '04:10')->withoutOverlapping()->name('store-geoip-update');
    }

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('geoip');
        $target = GeoIp::path();
        $part = $target.'.part';
        $gz = $part.'.gz';

        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = sprintf(self::URL, $month->format('Y-m'));
            $this->line("Downloading {$url} …");

            try {
                $response = Http::timeout(300)->withOptions(['sink' => $gz])->get($url);
            } catch (\Throwable $e) {
                $this->warn('  '.$e->getMessage());
                continue;
            }
            if (! $response->successful()) {
                $this->warn('  Not available (HTTP '.$response->status().').');
                @unlink($gz);
                continue;
            }

            try {
                $this->gunzip($gz, $part);
                $reader = new MmdbReader($part);   // must open…
                $reader->get('8.8.8.8');            // …and answer
                unset($reader);
            } catch (\Throwable $e) {
                $this->error('  The downloaded file is not usable: '.$e->getMessage());
                @unlink($part);
                @unlink($gz);
                continue;
            }

            @unlink($gz);
            if (! @rename($part, $target)) {
                @unlink($part);
                $this->error('Could not replace '.$target);

                return self::FAILURE;
            }

            $this->info('Visitor locations are up to date ('.\Illuminate\Support\Number::fileSize((int) filesize($target), 1).'). IP data by DB-IP.');

            return self::SUCCESS;
        }

        $this->error(is_file($target)
            ? 'Could not download a newer file; the current one is kept.'
            : 'Could not download the file. Visits are still counted, without countries and cities.');

        return self::FAILURE;
    }

    private function gunzip(string $from, string $to): void
    {
        $in = gzopen($from, 'rb');
        $out = fopen($to, 'wb');
        if (! $in || ! $out) {
            throw new \RuntimeException('Cannot unpack the download.');
        }
        while (! gzeof($in)) {
            $chunk = gzread($in, 1 << 20);
            if ($chunk === false) {
                throw new \RuntimeException('The download is damaged.');
            }
            fwrite($out, $chunk);
        }
        gzclose($in);
        fclose($out);
    }
}
