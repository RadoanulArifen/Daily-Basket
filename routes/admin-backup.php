<?php

use App\Http\Controllers\Admin\Settings\BackupController;
use Illuminate\Support\Facades\Route;

/*
| Settings → Backup & clean-up. Loaded from inside the settings group in routes/admin.php,
| so these are admin.settings.backup.* (Owner only, like all of Settings).
*/
Route::get('/backup', [BackupController::class, 'edit'])->name('backup.edit');
Route::post('/backup', [BackupController::class, 'store'])->middleware('throttle:6,1')->name('backup.store');
Route::put('/backup', [BackupController::class, 'update'])->name('backup.update');
Route::post('/backup/cleanup', [BackupController::class, 'cleanup'])->middleware('throttle:6,1')->name('backup.cleanup');
Route::get('/backup/{file}/download', [BackupController::class, 'download'])->where('file', '[A-Za-z0-9.\-]+')->name('backup.download');
Route::delete('/backup/{file}', [BackupController::class, 'destroy'])->where('file', '[A-Za-z0-9.\-]+')->name('backup.destroy');
