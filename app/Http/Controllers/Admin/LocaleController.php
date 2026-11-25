<?php

namespace App\Http\Controllers\Admin;

use App\Support\Localization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends AdminController
{
    public function __invoke(Request $request, Localization $localization): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in($localization->codes())],
        ]);

        $request->session()->put('admin_locale', $validated['locale']);
        $request->user('admin')?->forceFill(['locale' => $validated['locale']])->save();

        return back();
    }
}
