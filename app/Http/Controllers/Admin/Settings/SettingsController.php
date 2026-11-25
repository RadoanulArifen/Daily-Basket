<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\View\View;

class SettingsController extends AdminController
{
    public function __invoke(): View
    {
        $sections = [
            ['icon' => 'store', 'title' => __('admin.settings.general.title'), 'text' => __('admin.settings.general.summary'), 'url' => route('admin.settings.general.edit')],
            ['icon' => 'receipt', 'title' => __('admin.settings.tax.title'), 'text' => __('admin.settings.tax.summary'), 'url' => route('admin.settings.tax.edit')],
            ['icon' => 'shopping-cart', 'title' => __('admin.settings.checkout.title'), 'text' => __('admin.settings.checkout.summary'), 'url' => route('admin.settings.checkout.edit')],
            ['icon' => 'coins', 'title' => __('admin.currencies.title'), 'text' => __('admin.currencies.summary'), 'url' => route('admin.settings.currencies.index')],
            ['icon' => 'languages', 'title' => __('admin.settings.languages.title'), 'text' => __('admin.settings.languages.summary'), 'url' => null, 'badge' => 'English, العربية'],
            ['icon' => 'credit-card', 'title' => __('admin.settings.payments.title'), 'text' => __('admin.settings.payments.summary'), 'url' => route('admin.settings.payments.edit')],
            ['icon' => 'truck', 'title' => __('admin.settings.shipping.title'), 'text' => __('admin.settings.shipping.summary'), 'url' => route('admin.settings.shipping.edit')],
            ['icon' => 'mail', 'title' => __('admin.settings.email.title'), 'text' => __('admin.settings.email.summary'), 'url' => route('admin.settings.notifications.edit')],
            ['icon' => 'users', 'title' => __('admin.admins.title'), 'text' => __('admin.admins.summary'), 'url' => route('admin.admins.index')],
            ['icon' => 'chart-column', 'title' => __('admin.settings.tracking.title'), 'text' => __('admin.settings.tracking.summary'), 'url' => route('admin.settings.tracking.edit')],
            ['icon' => 'clipboard-list', 'title' => __('admin.activity.title'), 'text' => __('admin.activity.summary'), 'url' => route('admin.activity')],
            ['icon' => 'user-cog', 'title' => __('admin.account.title'), 'text' => __('admin.account.summary'), 'url' => route('admin.account.edit')],
        ];

        // Backup & clean-up: only once its routes are loaded (routes/admin-backup.php), so a
        // missing line in routes/admin.php can never break this page.
        if (\Illuminate\Support\Facades\Route::has('admin.settings.backup.edit')) {
            array_splice($sections, count($sections) - 2, 0, [['icon' => 'database', 'title' => __('admin_backup.title'), 'text' => __('admin_backup.summary_card'), 'url' => route('admin.settings.backup.edit')]]);
        }

        return view('admin.settings.index', compact('sections'));
    }
}
