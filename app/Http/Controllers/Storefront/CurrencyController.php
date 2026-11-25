<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Currency\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function __invoke(Request $request, CurrencyService $currencies): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', Rule::in($currencies->active()->keys()->all())],
        ]);

        $currencies->setCurrent($validated['currency']);

        return back();
    }
}
