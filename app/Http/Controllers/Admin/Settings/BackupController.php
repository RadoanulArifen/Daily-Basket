<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AuditLog;
use App\Services\Maintenance\Cleanup;
use App\Services\Maintenance\DatabaseBackup;
use App\Services\Maintenance\MaintenanceOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Settings → Backup & clean-up. Owner only: its routes are admin.settings.* (area "settings"). */
class BackupController extends AdminController
{
    public function __construct(private DatabaseBackup $backups, private MaintenanceOptions $options) {}

    public function edit(Cleanup $cleanup): View
    {
        return view('admin.settings.backup', [
            'backups' => $this->backups->all(),
            'options' => $this->options->all(),
            'counts' => $cleanup->counts(),
            'lastRun' => $this->options->last(),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $backup = $this->backups->create(auth('admin')->user());
            $this->backups->prune($this->options->all()['keep']);
            AuditLog::record('created', 'backup', null, $backup->name);
        } catch (Throwable $e) {
            report($e);

            return $this->done(false, __('admin_backup.failed', ['error' => $e->getMessage()]));
        }

        return $this->done(true, __('admin_backup.made', ['file' => $backup->name, 'size' => \Illuminate\Support\Number::fileSize($backup->size, 1)]));
    }

    public function download(string $file): BinaryFileResponse|RedirectResponse
    {
        // A public demo must never hand out its database.
        if (config('store.demo')) {
            return back()->with('error', __('admin.demo.blocked'));
        }

        $path = $this->backups->path($file);
        abort_if($path === null, 404);

        return response()->download($path, $file, ['Content-Type' => 'application/gzip', 'Cache-Control' => 'no-store']);
    }

    public function destroy(string $file): RedirectResponse
    {
        abort_unless($this->backups->delete($file), 404);
        AuditLog::record('deleted', 'backup', null, $file);

        return $this->done(true, __('admin_backup.deleted', ['file' => $file]));
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->input('section') === 'schedule') {
            $data = $request->validate(['schedule' => ['required', Rule::in(MaintenanceOptions::SCHEDULES)]]);
            $this->options->save(['schedule' => $data['schedule']]);
        } else {
            $data = $request->validate(['keep' => ['required', 'integer', Rule::in(MaintenanceOptions::KEEP)]]);
            $this->options->save(['auto' => $request->boolean('auto'), 'keep' => (int) $data['keep']]);
        }

        return back()->with('success', __('admin.settings.saved'));
    }

    public function cleanup(Request $request, Cleanup $cleanup): RedirectResponse
    {
        $data = $request->validate(['items' => ['required', 'array', 'min:1'], 'items.*' => [Rule::in(Cleanup::ITEMS)]]);
        $items = array_values(array_unique($data['items']));
        $this->options->save(['items' => $items]);   // the schedule cleans the same things

        try {
            $result = $cleanup->run($items, auth('admin')->user());
            $this->backups->prune($this->options->all()['keep']);
        } catch (Throwable $e) {
            report($e);

            return $this->done(false, __('admin_backup.clean_failed', ['error' => $e->getMessage()]), '#cleanup');
        }

        return $this->done(true, $result['summary'], '#cleanup');
    }

    private function done(bool $ok, string $message, string $anchor = '#backups'): RedirectResponse
    {
        return redirect()->to(route('admin.settings.backup.edit').$anchor)->with('backup_result', ['ok' => $ok, 'message' => $message]);
    }
}
