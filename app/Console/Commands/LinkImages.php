<?php

namespace App\Console\Commands;

use App\Support\StorageLink;
use Illuminate\Console\Command;

/**
 * Connects public/storage to storage/app/public so uploaded images are served by the web
 * server instead of through PHP.
 *
 * On Windows this makes a directory junction (mklink /J), which needs no administrator
 * rights — unlike the symlink `storage:link` makes — and it clears a leftover entry first,
 * which is what usually blocks a new link.
 */
class LinkImages extends Command
{
    protected $signature = 'store:link-images {--force : Remove whatever is at public/storage first} {--diagnose : Only report what is at public/storage, change nothing}';

    protected $description = 'Connect public/storage so uploaded images are served directly';

    public function handle(): int
    {
        $link = rtrim(public_path('storage'), '\\/');
        $target = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        if ($this->option('diagnose')) {
            $this->diagnose($link, $target);

            return self::SUCCESS;
        }

        if (StorageLink::state() === StorageLink::OK && ! $this->option('force')) {
            $this->info('Already connected: '.$link);

            return self::SUCCESS;
        }

        if (! $this->clear($link)) {
            return self::FAILURE;
        }

        $made = windows_os() ? $this->junction($link, $target) : @symlink($target, $link);
        clearstatcache(true, $link);

        if (! $made) {
            return $this->explain($link, $target);
        }

        if (StorageLink::state() !== StorageLink::OK) {
            // Whatever was made is not usable (Windows sometimes makes a file link instead of
            // a folder one). Clear it so the next attempt — or the PHP fallback — starts clean.
            $this->warn('The link was made, but files cannot be read through it. Removing it again.');
            @unlink($link);
            @rmdir($link);
            clearstatcache(true, $link);

            $this->newLine();
            $this->diagnose($link, $target);

            return $this->explain($link, $target);
        }

        $this->info('Connected '.$link.' → '.$target);
        $this->line('Images are now served by the web server.');

        return self::SUCCESS;
    }

    /** Print exactly what is on disk, so a stubborn case can be worked out. */
    private function diagnose(string $link, string $target): void
    {
        clearstatcache();
        $this->line('link   : '.$link);
        $this->line('target : '.$target);
        $this->newLine();
        foreach ([$link => 'public/storage', $target => 'storage/app/public'] as $path => $label) {
            clearstatcache(true, $path);
            $this->line($label.':');
            $this->line('  exists     : '.var_export(file_exists($path), true));
            $this->line('  is_link    : '.var_export(is_link($path), true));
            $this->line('  is_dir     : '.var_export(is_dir($path), true));
            $this->line('  is_file    : '.var_export(is_file($path), true));
            $this->line('  realpath   : '.var_export(realpath($path), true));
            if (is_link($path)) {
                $this->line('  points to  : '.var_export(@readlink($path), true));
            }
            if (is_dir($path)) {
                $this->line('  entries    : '.implode(', ', array_slice(array_diff((array) @scandir($path), ['.', '..']), 0, 5)));
            }
            $this->newLine();
        }

        $this->line('state  : '.StorageLink::state());
        $this->line('exec available: '.var_export(function_exists('exec'), true));
        if (windows_os() && function_exists('exec')) {
            exec('cmd /c dir /a '.escapeshellarg(dirname($link)).' 2>&1', $dir);
            foreach (array_filter($dir, fn ($l) => str_contains(mb_strtolower($l), 'storage')) as $line) {
                $this->line('dir    : '.trim($line));
            }
        }
        $this->newLine();
        $this->line('Images work either way: without a link they are served through PHP.');
    }

    /** Remove a leftover entry (usually a dead link from a failed attempt). */
    private function clear(string $link): bool
    {
        clearstatcache(true, $link);
        if (! is_link($link) && ! file_exists($link)) {
            return true;
        }

        $this->line('Removing the leftover '.$link.' …');
        // A link to a folder: rmdir on Windows, unlink elsewhere. Try both, either order.
        @rmdir($link);
        clearstatcache(true, $link);
        if (is_link($link) || file_exists($link)) {
            @unlink($link);
            clearstatcache(true, $link);
        }

        if (is_link($link) || file_exists($link)) {
            $this->error('Could not remove it — something is using it (close Explorer windows and editors, then try again), or it is a real folder with files in it.');
            $this->line('Remove it yourself and run this command again:');
            $this->line(windows_os() ? '  cmd /c rmdir /s /q "'.$link.'"' : '  rm -rf "'.$link.'"');

            return false;
        }

        return true;
    }

    /** mklink is built into the Windows shell, so it has to run through cmd. */
    private function junction(string $link, string $target): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        exec('cmd /c mklink /J '.escapeshellarg($link).' '.escapeshellarg($target).' 2>&1', $output, $status);
        clearstatcache(true, $link);

        // Check by resolving the path: PHP cannot stat a junction (is_dir reports false even
        // when the junction is perfectly good), so is_dir() must not be the test here.
        if (StorageLink::state() === StorageLink::OK) {
            return true;
        }

        $this->line('  mklink said: '.trim(implode(' ', $output) ?: 'nothing'));

        if (is_link($link) || file_exists($link)) {
            return false;   // something is there but unusable; do not pile another link on top
        }

        // Second try: a symlink, which works with Developer Mode on or in an admin terminal.
        return @symlink($target, $link);
    }

    private function explain(string $link, string $target): int
    {
        $this->error('Could not connect '.$link.'.');
        $this->newLine();
        $this->line('Your images still work — they are served through PHP, which is just slower.');
        $this->line('To create the link by hand, open <options=bold>Command Prompt</> (not PowerShell) in your project folder:');
        $this->line('  mklink /J public\storage storage\app\public');
        $this->line('In PowerShell the same command is:');
        $this->line('  cmd /c mklink /J public\storage storage\app\public');
        $this->line('Or open the terminal as Administrator and run: php artisan storage:link');
        $this->newLine();
        $this->line('If none of that works, let Apache serve the folder directly instead. In');
        $this->line('C:\xampp\apache\conf\httpd.conf add, then restart Apache:');
        $this->line('  Alias /storage "'.str_replace('\\', '/', $target).'"');
        $this->line('  <Directory "'.str_replace('\\', '/', $target).'">');
        $this->line('      Require all granted');
        $this->line('  </Directory>');
        $this->newLine();
        $this->line('Link: '.$link);
        $this->line('Target: '.$target);

        return self::FAILURE;
    }
}
