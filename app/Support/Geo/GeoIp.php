<?php

namespace App\Support\Geo;

use Illuminate\Support\Facades\Storage;

/**
 * Country and city for an IP address, from DB-IP's free "IP to City Lite" file
 * (storage/app/private/geoip/dbip-city-lite.mmdb, kept up to date by `php artisan store:geoip-update`).
 * Without the file everything still works; places are just unknown.
 */
class GeoIp
{
    public const FILE = 'geoip/dbip-city-lite.mmdb';

    private ?MmdbReader $reader = null;
    private bool $tried = false;

    public static function path(): string
    {
        return Storage::disk('local')->path(self::FILE);
    }

    public function ready(): bool
    {
        return is_file(self::path());
    }

    /** @return array{country: ?string, city: ?string, lat: ?float, lon: ?float} */
    public function lookup(?string $ip): array
    {
        $none = ['country' => null, 'city' => null, 'lat' => null, 'lon' => null];
        if (! $ip || ! ($reader = $this->reader())) {
            return $none;
        }

        try {
            $r = $reader->get($ip);
        } catch (\Throwable) {
            return $none;
        }
        if (! is_array($r)) {
            return $none;
        }

        $country = strtoupper((string) ($r['country']['iso_code'] ?? ''));
        $city = $r['city']['names']['en'] ?? null;

        return [
            'country' => preg_match('/^[A-Z]{2}$/', $country) ? $country : null,
            'city' => is_string($city) && $city !== '' ? mb_substr($city, 0, 100) : null,
            'lat' => isset($r['location']['latitude']) ? round((float) $r['location']['latitude'], 4) : null,
            'lon' => isset($r['location']['longitude']) ? round((float) $r['location']['longitude'], 4) : null,
        ];
    }

    private function reader(): ?MmdbReader
    {
        if (! $this->tried) {
            $this->tried = true;
            try {
                $this->reader = $this->ready() ? new MmdbReader(self::path()) : null;
            } catch (\Throwable $e) {
                report($e);
                $this->reader = null;
            }
        }

        return $this->reader;
    }
}
