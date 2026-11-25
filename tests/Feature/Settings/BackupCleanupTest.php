<?php

namespace Tests\Feature\Settings;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Services\Maintenance\Cleanup;
use App\Services\Maintenance\DatabaseBackup;
use App\Services\Maintenance\MaintenanceOptions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\Feature\Storefront\StorefrontTestCase;

/** Settings → Backup & clean-up, store:backup and store:cleanup. */
class BackupCleanupTest extends StorefrontTestCase
{
    private Admin $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake(config('store.uploads.disk'));
        $this->owner = Admin::factory()->create(['name' => 'Store Owner']);
    }

    private function backups(): DatabaseBackup
    {
        return app(DatabaseBackup::class);
    }

    public function test_only_the_owner_can_open_the_page(): void
    {
        $this->actingAs($this->owner, 'admin')->get('/admin/settings/backup')->assertOk()
            ->assertSee('Back up now')->assertSee('Abandoned guest carts')->assertSee('There is no restore button');

        foreach (['manager', 'orders', 'content'] as $role) {
            $this->actingAs(Admin::factory()->create(['role' => $role]), 'admin');
            $this->get('/admin/settings/backup')->assertForbidden();
            $this->post('/admin/settings/backup')->assertForbidden();
            $this->post('/admin/settings/backup/cleanup', ['items' => ['carts']])->assertForbidden();
        }
    }

    public function test_back_up_now_writes_a_named_gzip_that_restores_every_row_on_an_empty_database(): void
    {
        $this->makeProduct('Brass Dallah');
        DB::table('sessions')->insert(['id' => 'abc', 'payload' => 'x', 'last_activity' => time()]);
        DB::table('settings')->insert(['group' => 'test', 'key' => 'quote', 'value' => json_encode("O'Reilly \"x\" \\ ü 😀")]);

        $before = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
            ->mapWithKeys(fn ($t) => [$t->name => in_array($t->name, DatabaseBackup::SKIP_ROWS, true) ? 0 : DB::table($t->name)->count()]);

        $this->actingAs($this->owner, 'admin')->post('/admin/settings/backup')
            ->assertRedirect(route('admin.settings.backup.edit').'#backups')
            ->assertSessionHas('backup_result', fn ($r) => $r['ok'] === true);

        $backup = $this->backups()->all()->first();
        $this->assertMatchesRegularExpression('/^backup-\d{4}-\d{2}-\d{2}-\d{6}-admin'.$this->owner->id.'\.sql\.gz$/', $backup->name);
        $this->assertSame('Store Owner', $backup->by);
        $this->assertFalse(Storage::disk('local')->exists('backups/'.$backup->name.'.part'));

        // Restore into a brand-new, empty database and compare every table.
        $sql = gzdecode(Storage::disk('local')->get('backups/'.$backup->name));
        $file = tempnam(sys_get_temp_dir(), 'restore');
        $restored = new PDO('sqlite:'.$file);
        $restored->exec($sql);

        $this->assertGreaterThan(20, $before->count());
        foreach ($before as $table => $expected) {
            $got = (int) $restored->query('SELECT COUNT(*) FROM "'.$table.'"')->fetchColumn();
            $this->assertSame($expected, $got, "rows in {$table}");
        }
        $this->assertGreaterThan(0, $before['products']);
        $this->assertSame(json_encode("O'Reilly \"x\" \\ ü 😀"), $restored->query("SELECT value FROM settings WHERE \"group\" = 'test'")->fetchColumn());
        @unlink($file);

        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'backup', 'action' => 'created', 'subject_label' => $backup->name]);
    }

    public function test_pruning_keeps_only_the_newest(): void
    {
        foreach (range(1, 5) as $i) {
            CarbonImmutable::setTestNow('2026-10-0'.$i.' 03:00:00');
            $this->backups()->create();
        }
        CarbonImmutable::setTestNow();

        $this->assertSame(2, $this->backups()->prune(3));
        $this->assertSame(['backup-2026-10-05-030000-auto.sql.gz', 'backup-2026-10-04-030000-auto.sql.gz', 'backup-2026-10-03-030000-auto.sql.gz'],
            $this->backups()->all()->pluck('name')->all());

        $this->artisan('store:backup', ['--keep' => 2])->assertSuccessful();
        $this->assertCount(2, $this->backups()->all());
    }

    public function test_download_and_delete_refuse_bad_names(): void
    {
        $name = $this->backups()->create()->name;
        Storage::disk('local')->put('secret.txt', 'nope');
        $this->actingAs($this->owner, 'admin');

        foreach (['..%2Fsecret.txt', 'secret.txt', 'backup-2026-10-05-030000-auto.sql', 'backup-x.sql.gz', 'backup-2026-10-05-030000-auto.sql.gz.part'] as $bad) {
            $this->get("/admin/settings/backup/{$bad}/download")->assertNotFound();
            $this->delete("/admin/settings/backup/{$bad}")->assertNotFound();
        }
        $this->assertTrue(Storage::disk('local')->exists('secret.txt'));

        $this->get("/admin/settings/backup/{$name}/download")->assertOk()->assertDownload($name);

        config(['store.demo' => true]);
        $this->get("/admin/settings/backup/{$name}/download")->assertRedirect();   // never in a public demo
        config(['store.demo' => false]);

        $this->delete("/admin/settings/backup/{$name}")->assertRedirect();
        $this->assertCount(0, $this->backups()->all());
    }

    public function test_clean_up_backs_up_first_and_deletes_only_old_data(): void
    {
        $old = now()->subDays(45);
        $user = \App\Models\User::factory()->create();

        $oldGuest = DB::table('carts')->insertGetId(['token' => 'old-guest', 'created_at' => $old, 'updated_at' => $old]);
        $newGuest = DB::table('carts')->insertGetId(['token' => 'new-guest', 'created_at' => now(), 'updated_at' => now()]);
        $oldCustomer = DB::table('carts')->insertGetId(['token' => 'old-customer', 'user_id' => $user->id, 'created_at' => $old, 'updated_at' => $old]);
        DB::table('sessions')->insert([
            ['id' => 'expired', 'payload' => 'x', 'last_activity' => now()->subDays(2)->timestamp],
            ['id' => 'live', 'payload' => 'x', 'last_activity' => time()],
        ]);
        DB::table('failed_jobs')->insert([
            ['uuid' => 'a', 'connection' => 'db', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()->subDays(40)],
            ['uuid' => 'b', 'connection' => 'db', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()],
        ]);
        DB::table('funnel_counts')->insert([
            ['day' => now()->subYears(2)->toDateString(), 'stage' => 'visit', 'count' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['day' => now()->toDateString(), 'stage' => 'visit', 'count' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('order_drafts')->insert([
            ['admin_id' => $this->owner->id, 'name' => 'old', 'payload' => '{}', 'created_at' => now()->subDays(90), 'updated_at' => now()->subDays(90)],
            ['admin_id' => $this->owner->id, 'name' => 'new', 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // An upload folder nobody points to (old), one that is in use, and a fresh orphan.
        $disk = Storage::disk(config('store.uploads.disk'));
        $orphan = 'media/2025/01/'.\Illuminate\Support\Str::uuid();
        $fresh = 'media/2026/10/'.\Illuminate\Support\Str::uuid();
        $used = 'media/2025/01/'.\Illuminate\Support\Str::uuid();
        foreach ([$orphan, $fresh, $used] as $dir) {
            $disk->put("$dir/medium.webp", 'img');
        }
        foreach ([$orphan, $used] as $dir) {
            touch($disk->path($dir), time() - 3 * 86400);
            touch($disk->path("$dir/medium.webp"), time() - 3 * 86400);
        }
        DB::table('media')->insert(['disk' => config('store.uploads.disk'), 'directory' => $used, 'original_name' => 'u.jpg', 'mime_type' => 'image/webp', 'size' => 1, 'width' => 10, 'height' => 10, 'sizes' => '[]', 'created_at' => now(), 'updated_at' => now()]);

        $counts = app(Cleanup::class)->counts();
        $this->assertSame(['carts' => 1, 'sessions' => 1, 'failed_jobs' => 1, 'statistics' => 1, 'drafts' => 1, 'media' => 1], $counts);

        $this->actingAs($this->owner, 'admin')->post('/admin/settings/backup/cleanup', ['items' => Cleanup::ITEMS])
            ->assertSessionHas('backup_result', fn ($r) => $r['ok'] && str_contains($r['message'], 'Backed up first'));

        // A backup exists, made before the deletions (it still has the old cart).
        $backup = $this->backups()->all()->first();
        $this->assertNotNull($backup);
        $this->assertStringContainsString("'old-guest'", gzdecode(Storage::disk('local')->get('backups/'.$backup->name)));

        $this->assertDatabaseMissing('carts', ['id' => $oldGuest]);
        $this->assertDatabaseHas('carts', ['id' => $newGuest]);
        $this->assertDatabaseHas('carts', ['id' => $oldCustomer]);   // a customer's cart is kept
        $this->assertSame(['live'], DB::table('sessions')->pluck('id')->all());
        $this->assertSame(['b'], DB::table('failed_jobs')->pluck('uuid')->all());
        $this->assertSame(1, DB::table('funnel_counts')->count());
        $this->assertSame(['new'], DB::table('order_drafts')->pluck('name')->all());
        $this->assertFalse($disk->exists($orphan));
        $this->assertTrue($disk->exists($fresh));
        $this->assertTrue($disk->exists($used));

        $this->assertSame(Cleanup::ITEMS, app(MaintenanceOptions::class)->all()['items']);
        $this->assertNotNull(app(MaintenanceOptions::class)->last());
        $this->assertTrue(AuditLog::query()->where('subject_type', 'cleanup')->exists());
        $this->get('/admin/settings/backup')->assertSee('Last clean-up');
    }

    public function test_artisan_cleanup_uses_the_ticked_items_and_backs_up(): void
    {
        DB::table('sessions')->insert(['id' => 'expired', 'payload' => 'x', 'last_activity' => now()->subDays(2)->timestamp]);
        app(MaintenanceOptions::class)->save(['items' => ['sessions']]);

        $this->artisan('store:cleanup --dry-run')->assertSuccessful();
        $this->assertSame(1, DB::table('sessions')->count());

        $this->artisan('store:cleanup --auto')->assertSuccessful();
        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertStringEndsWith('-auto.sql.gz', $this->backups()->all()->first()->name);
    }

    public function test_settings_and_schedule(): void
    {
        $this->actingAs($this->owner, 'admin');
        $this->put('/admin/settings/backup', ['section' => 'backups', 'auto' => '1', 'keep' => 14])->assertSessionHas('success');
        $this->put('/admin/settings/backup', ['section' => 'schedule', 'schedule' => 'weekly'])->assertSessionHas('success');
        $this->put('/admin/settings/backup', ['section' => 'backups', 'keep' => 99])->assertSessionHasErrors('keep');

        $options = app(MaintenanceOptions::class);
        $this->assertSame(['auto' => true, 'keep' => 14, 'schedule' => 'weekly', 'items' => Cleanup::DEFAULT_ITEMS], $options->all());
        $this->assertTrue($options->dueToday(CarbonImmutable::parse('2026-10-25')));   // a Sunday
        $this->assertFalse($options->dueToday(CarbonImmutable::parse('2026-10-26')));

        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map->command->implode(' ');
        $this->assertStringContainsString('store:backup --auto', $events);
        $this->assertStringContainsString('store:cleanup --auto', $events);
    }
}
