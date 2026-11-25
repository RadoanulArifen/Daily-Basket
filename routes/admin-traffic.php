<?php

use App\Http\Controllers\Admin\Insights\TrafficController;
use Illuminate\Support\Facades\Route;

/*
| Analytics → Traffic. Loaded from routes/admin.php next to the Analytics route.
| Area "analytics" (config/admin-permissions.php: 'admin.traffic*' => 'analytics').
*/
Route::get('/analytics/traffic', [TrafficController::class, 'index'])->name('traffic');
Route::get('/analytics/traffic/live', [TrafficController::class, 'live'])->middleware('throttle:30,1')->name('traffic.live');
