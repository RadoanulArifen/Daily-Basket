# Upgrading FreshNest 1.0.57 → 1.1.0

**First make sure the server runs PHP 8.2.35 or newer (8.4 recommended)** — older builds lack
the PHP2Shell security fix. Check with `php -v` and in your hosting panel (the website and the
command line can use different PHP versions).

Copy the 1.1.0 files over your installation (keep your `.env`, `storage/` and uploads). The
package includes a ready-built `public/build`, so Node is optional on the server. Then:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan store:geoip-update
php artisan optimize:clear
php artisan optimize
php artisan store:launch-check
php artisan store:web-check
```

- `migrate` adds two tables/columns: guided-tour progress (1.0.60) and the visitor-traffic
  tables (1.1.0). Nothing existing is changed.
- `store:geoip-update` downloads DB-IP's free IP-to-city file for the traffic map (a large
  download, once). It then updates itself monthly through the scheduler. Without it, visits are
  still counted, just without countries.
- Make sure the cron line `* * * * * cd /path/to/site && php artisan schedule:run` is set:
  automatic backups, clean-up and the monthly IP-file update run from it.
- If you build the assets yourself instead: `npm ci && npm run build` (the editor changed from
  Trix to TinyMCE, so the old `node_modules` must be replaced).
- `optimize:clear` matters: compiled views from 1.0.57 still contain the old editor and the old
  Livewire script tag.
- If `public/vendor/livewire` exists, delete it (no longer used since 1.0.61).
