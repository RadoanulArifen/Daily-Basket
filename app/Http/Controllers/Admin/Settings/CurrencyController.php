<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Settings\CurrencyRequest;
use App\Models\Currency;
use App\Services\Currency\CurrencyService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurrencyController extends AdminController
{
    public function __construct(
        private CurrencyService $currencies,
        private SettingsService $settings,
    ) {}

    public function index(): View
    {
        return view('admin.settings.currencies.index', [
            'currencies' => Currency::query()->ordered()->get(),
            'base' => $this->currencies->base(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.currencies.create', [
            'currency' => new Currency(['decimal_places' => 2, 'is_active' => true, 'sort_order' => 0]),
            'base' => $this->currencies->base(),
        ]);
    }

    public function store(CurrencyRequest $request): RedirectResponse
    {
        Currency::create($request->validated());
        $this->changed();

        return redirect()->route('admin.settings.currencies.index')
            ->with('success', __('admin.currencies.created'));
    }

    public function edit(Currency $currency): View
    {
        return view('admin.settings.currencies.edit', [
            'currency' => $currency,
            'base' => $this->currencies->base(),
        ]);
    }

    public function update(CurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $data = $request->validated();

        if ($currency->is_base) {
            // The base currency's code and rate never change: every price is stored in it.
            unset($data['code'], $data['exchange_rate']);
            $data['is_active'] = true;
        }

        $currency->update($data);
        $this->changed();

        return redirect()->route('admin.settings.currencies.index')
            ->with('success', __('admin.currencies.updated'));
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_base) {
            return back()->with('error', __('admin.currencies.cannot_delete_base'));
        }

        $currency->delete();
        $this->changed();

        return redirect()->route('admin.settings.currencies.index')
            ->with('success', __('admin.currencies.deleted'));
    }

    private function changed(): void
    {
        $this->currencies->flush();
        $this->settings->set('currencies.reviewed_at', now()->toIso8601String());
    }

    /** Switch a currency on or off from the list, without opening the form. */
    public function toggle(Currency $currency): RedirectResponse
    {
        if ($currency->is_base) {
            return back()->with('error', __('admin.currencies.base_always_active'));
        }

        $currency->update(['is_active' => ! $currency->is_active]);
        $this->changed();

        return back()->with('success', __($currency->is_active ? 'admin.currencies.activated' : 'admin.currencies.deactivated', ['code' => $currency->code]));
    }

    /**
     * Make another currency the base one.
     *
     * Every catalogue price is stored in the base currency, so they are all converted at the
     * new currency's rate — in one transaction, so a failure leaves the shop as it was.
     *
     * Orders, refunds and payments are deliberately left alone: they record what was actually
     * charged at the time, and rewriting them would falsify your books.
     */
    public function makeBase(Currency $currency, \App\Services\Currency\BaseCurrencySwitch $switch): RedirectResponse
    {
        if ($currency->is_base) {
            return back();
        }

        try {
            $switch->to($currency);
        } catch (\Throwable $e) {
            return back()->with('error', __('admin.currencies.base_change_failed', ['reason' => $e->getMessage()]));
        }

        $this->changed();

        return back()->with('success', __('admin.currencies.base_changed', ['code' => $currency->code]));
    }
}
