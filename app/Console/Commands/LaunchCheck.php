<?php

namespace App\Console\Commands;

use App\Models\Discount;
use App\Models\User;
use App\Services\Payments\StripeGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Run on the live server before (and after) launch:  php artisan store:launch-check
 * Anything marked FAIL must be fixed; WARN items are worth a look.
 */
class LaunchCheck extends Command
{
    protected $signature = 'store:launch-check';

    protected $description = 'Check that this installation is safe and ready to take real orders';

    private array $rows = [];

    public function handle(): int
    {
        $s = settings();

        // --- Application
        $this->check('App environment is "production"', app()->isProduction(), 'Set APP_ENV=production in .env');
        $this->check('Debug mode is off', ! config('app.debug'), 'Set APP_DEBUG=false — debug pages reveal secrets');
        $this->check('App key is set', filled(config('app.key')), 'Run php artisan key:generate');
        $this->check('PHP '.PHP_VERSION.' has the PHP2Shell security fix', self::phpIsPatched(PHP_VERSION),
            'URGENT: update PHP to 8.4.27 / 8.3.36 / 8.2.35 / 8.5.12 or newer (CVE-2026-109618, CVE-2026-109619). Linux distributions sometimes patch without changing the number: then confirm with your host', warn: true);
        $dbDriver = (string) config('database.default');
        $dbPassword = (string) config("database.connections.{$dbDriver}.password");
        $this->check('Database password is set', $dbDriver === 'sqlite' || (strlen($dbPassword) >= 12 && ! str_contains($dbPassword, 'your_')),
            'Set a strong DB_PASSWORD (12+ characters) in .env — never an empty or placeholder password');
        $this->check('Site address uses HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL=https://your-domain');
        $this->check('Secure session cookies', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true', warn: true);
        $this->check('Configuration is cached', app()->configurationIsCached(), 'Run php artisan optimize (deploy.sh does this)', warn: true);

        // --- Database and files
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (Throwable) {
            $dbOk = false;
        }
        $this->check('Database connection works', $dbOk, 'Check DB_* settings in .env');

        if ($dbOk) {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());
            $this->check('All database updates applied', ! $pending, 'Run php artisan migrate --force');
        }

        $this->check('Uploaded images are reachable (storage link)', is_link(public_path('storage')) || is_dir(public_path('storage')), 'Run php artisan storage:link');
        $this->check('Front-end assets are built', is_file(public_path('build/manifest.json')), 'Run npm ci && npm run build');
        $missing = $this->missingBuildFiles();
        $this->check('Every built file in the manifest is on the server', $missing === [],
            count($missing).' missing (e.g. '.($missing[0] ?? '').'): upload the whole public/build folder again, or run npm ci && npm run build');
        $this->check('No old Livewire script copy in public/vendor/livewire', ! is_dir(public_path('vendor/livewire')), 'Delete public/vendor/livewire (no longer used since 1.0.61)', warn: true);
        $this->check('Storage folder is writable', is_writable(storage_path('logs')) && is_writable(storage_path('framework/cache')), 'Fix permissions: see docs/deployment.md');

        // --- Email and background work
        $mailer = config('mail.default');
        $this->check('Emails are really sent (not just logged)', ! in_array($mailer, ['log', 'array'], true), 'Set MAIL_MAILER=smtp and the Gmail settings in .env');
        $this->check('Email "from" address is set', ! str_ends_with((string) config('mail.from.address'), '@example.com'), 'Set MAIL_FROM_ADDRESS to your Gmail address');
        // Sending as part of the request is the safe default: a queue only helps when a
        // worker is actually running, and without one the email never leaves at all.
        $this->check(
            'Email sending is set up',
            config('queue.default') === 'sync' || $this->hasQueueWorker(),
            'Email is queued but no worker has run recently: start the worker (vibe-queue service) or set QUEUE_CONNECTION=sync',
            warn: true,
        );

        if ($dbOk && config('queue.default') === 'database') {
            $stuck = DB::table('jobs')->where('created_at', '<', now()->subMinutes(10)->timestamp)->count();
            $this->check('Queue worker is running (no stuck emails)', $stuck === 0, "{$stuck} job(s) waiting over 10 minutes: start the worker (vibe-queue service)");
            $failed = DB::table('failed_jobs')->count();
            $this->check('No failed background jobs', $failed === 0, "{$failed} failed: run php artisan queue:failed to see why", warn: true);
        }

        $beat = Cache::get('schedule:heartbeat');
        $this->check('Scheduler cron is running', $beat && Carbon::parse($beat)->gt(now()->subMinutes(5)),
            'Add the cron line from docs/deployment.md (cancels unpaid card orders, cleans up, prunes logs)');

        // --- Payments
        if ($s->bool('payments.stripe_enabled')) {
            $stripe = app(StripeGateway::class);
            $this->check('Stripe uses live keys', ! $stripe->isTestMode(), 'Replace sk_test_/pk_test_ keys with live ones before launch', warn: ! app()->isProduction());
            $this->check('Stripe webhook secret is set', filled(config('services.stripe.webhook_secret')), 'Create the webhook in Stripe and set STRIPE_WEBHOOK_SECRET');
        }

        // --- Store details
        $this->check('Store email is set', filled($s->get('general.store_email')), 'Admin → Settings → General');
        $this->check('Store phone is set', filled($s->get('general.store_phone')), 'Admin → Settings → General', warn: true);
        if ($s->bool('tax.enabled')) {
            $this->check('VAT registration number (TRN) is set', filled($s->get('general.trn')), 'Admin → Settings → General: it must appear on tax invoices');
        }

        // --- Demo data left over
        if ($dbOk) {
            $this->check('Demo customer account removed', ! User::query()->where('email', 'customer@example.com')->exists(), 'Delete customer@example.com (Admin → Customers) or reinstall without demo data', warn: true);
            $this->check('Demo discount WELCOME10 reviewed', ! Discount::query()->where('code', 'WELCOME10')->where('is_active', true)->exists(), 'Switch it off in Admin → Marketing → Discounts if you don’t want it', warn: true);
            $this->check('Demo products replaced', ! DB::table('product_variants')->where('sku', 'like', 'SH-%')->whereNull('deleted_at')->exists(), 'Replace or delete the Demo Store demo products', warn: true);
        }

        // --- Backups
        $latest = collect(glob(storage_path('backups/*.sql.gz')) ?: [])->map(fn ($f) => filemtime($f))->max();
        $this->check('A backup was made in the last 2 days', $latest && $latest > now()->subDays(2)->timestamp, 'Set up deploy/backup.sh in cron (docs/deployment.md)', warn: true);

        $this->table(['', 'Check', 'What to do'], $this->rows);

        $fails = collect($this->rows)->where(0, 'FAIL')->count();
        $warns = collect($this->rows)->where(0, 'WARN')->count();

        if ($fails) {
            $this->error("{$fails} problem(s) must be fixed before taking real orders.");

            return self::FAILURE;
        }

        $warns ? $this->warn("Ready, with {$warns} thing(s) worth checking.") : $this->info('Everything looks ready.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $ok, string $fix, bool $warn = false): void
    {
        $this->rows[] = [$ok ? 'OK' : ($warn ? 'WARN' : 'FAIL'), $label, $ok ? '' : $fix];
    }

    /** The first release of each PHP branch with the PHP2Shell fix (21 Oct 2026). Older branches get none. */
    public const PHP_PATCHED = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

    public static function phpIsPatched(string $version): bool
    {
        $branch = implode('.', array_slice(explode('.', $version), 0, 2));
        if (isset(self::PHP_PATCHED[$branch])) {
            return version_compare($version, self::PHP_PATCHED[$branch], '>=');
        }

        return version_compare($branch, '8.5', '>');   // a newer branch than any listed
    }

    /** Files listed in public/build/manifest.json that are not on disk (a half-uploaded build → 404s). */
    private function missingBuildFiles(): array
    {
        $manifest = json_decode((string) @file_get_contents(public_path('build/manifest.json')), true);
        if (! is_array($manifest)) {
            return [];
        }

        return collect($manifest)
            ->flatMap(fn ($entry) => [$entry['file'] ?? null, ...($entry['css'] ?? []), ...($entry['assets'] ?? [])])
            ->filter()->unique()
            ->reject(fn ($file) => is_file(public_path('build/'.$file)))
            ->values()->all();
    }

    /** True when a queue worker has picked something up in the last quarter of an hour. */
    private function hasQueueWorker(): bool
    {
        try {
            return DB::table('jobs')->where('created_at', '<', now()->subMinutes(10)->timestamp)->count() === 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
