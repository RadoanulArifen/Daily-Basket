<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Mail server (SMTP) and reCAPTCHA settings. The values are saved by CredentialsController. */
class MailServerController extends AdminController
{
    public function edit(): View
    {
        return view('admin.settings.mail-server');
    }

    /**
     * Send the emails waiting in the queue now.
     *
     * A store with no queue worker would otherwise never send them. This works through the
     * waiting jobs for up to a minute and reports what is left, so a long backlog can be
     * cleared by pressing the button again.
     */
    public function runQueue(): RedirectResponse
    {
        if (! Schema::hasTable('jobs')) {
            return back();
        }

        $before = DB::table('jobs')->count();
        $failedBefore = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 55,
            '--tries' => 3,
        ]);

        $left = DB::table('jobs')->count();
        $failed = (Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0) - $failedBefore;
        $sent = max(0, $before - $left - $failed);

        if ($failed > 0) {
            // Something is wrong with those messages or the mail server; saying so is more
            // use than telling the owner to press the button again.
            return back()->with('warning', __('admin.settings.mail_server.queue_failed', ['sent' => $sent, 'failed' => $failed]));
        }

        if ($left > 0) {
            return back()->with('warning', __('admin.settings.mail_server.queue_partly_sent', ['sent' => $sent, 'left' => $left]));
        }

        return back()->with('success', trans_choice('admin.settings.mail_server.queue_sent', $sent, ['count' => $sent]));
    }
}
