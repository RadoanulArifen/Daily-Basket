<?php

namespace App\Http\Controllers\Admin\OnlineStore;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AuditLog;
use App\Models\CodeVersion;
use App\Services\Settings\SettingsService;
use App\Services\Theme\CustomCode;
use App\Services\Theme\ThemeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Online Store: Themes (Customize), Custom code and Preferences. */
class OnlineStoreController extends AdminController
{
    public function themes(ThemeSettings $theme): View
    {
        return view('admin.online-store.themes', ['theme' => $theme]);
    }

    public function customize(ThemeSettings $theme, SettingsService $settings): View
    {
        return view('admin.online-store.customize', [
            'values' => $theme->draft(),
            'sections' => $theme->sections($theme->draft()),
            'general' => $settings->group('general'),
            'hasDraft' => $theme->hasDraft(),
        ]);
    }

    public function saveDraft(Request $request, ThemeSettings $theme, SettingsService $settings): RedirectResponse
    {
        $hex = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $data = $request->validate([
            'color_primary' => $hex, 'color_accent' => $hex, 'color_background' => $hex, 'color_surface' => $hex,
            'font' => ['required', Rule::in(array_keys(ThemeSettings::FONTS))],
            'sections' => ['required', 'array'],
            'sections.*.key' => ['required', Rule::in(ThemeSettings::keys())],
            'sections.*.visible' => ['nullable', 'boolean'],
            'announcement_text.*' => ['nullable', 'string', 'max:160'],
            'trust_badges.*' => ['nullable', 'string', 'max:300'],
            'footer_categories_heading.*' => ['nullable', 'string', 'max:40'],
        ]);

        $theme->saveDraft([
            'color_primary' => strtolower($data['color_primary']), 'color_accent' => strtolower($data['color_accent']),
            'color_background' => strtolower($data['color_background']), 'color_surface' => strtolower($data['color_surface']),
            'font' => $data['font'],
            'sections' => collect($data['sections'])->map(fn ($s) => ['key' => $s['key'], 'visible' => (bool) ($s['visible'] ?? false)])->values()->all(),
        ]);

        // Wording shown on the storefront straight away, like the announcement bar.
        $settings->setMany('theme', [
            'trust_badges' => array_filter($data['trust_badges'] ?? []),
            'footer_categories_heading' => array_filter($data['footer_categories_heading'] ?? []),
        ]);

        // The announcement bar is the same setting as in Settings → Store details (live immediately).
        $settings->setMany('general', [
            'announcement_enabled' => $request->boolean('announcement_enabled'),
            'announcement_text' => array_filter($data['announcement_text'] ?? []),
        ]);

        return back()->with('success', __('admin.online.draft_saved'));
    }

    public function publish(ThemeSettings $theme): RedirectResponse
    {
        $theme->publish();
        AuditLog::record('updated', 'theme', null, __('admin.online.theme_name'));

        return redirect()->route('admin.online-store.customize')->with('success', __('admin.online.published'));
    }

    public function discard(ThemeSettings $theme): RedirectResponse
    {
        $theme->discard();

        return redirect()->route('admin.online-store.customize')->with('success', __('admin.online.discarded'));
    }

    /** Opens the store showing the draft (for the preview pane and "Preview" button). */
    public function previewStart(Request $request): RedirectResponse
    {
        $request->session()->put('theme_preview', true);

        return redirect(store_url('/', config('store.default_locale')));
    }

    public function previewExit(Request $request): RedirectResponse
    {
        $request->session()->forget('theme_preview');

        return redirect()->route('admin.online-store.customize');
    }

    public function code(CustomCode $code): View
    {
        return view('admin.online-store.code', ['code' => $code->get(), 'versions' => CodeVersion::query()->with('admin:id,name')->latest('id')->limit(CodeVersion::KEEP)->get()]);
    }

    public function saveCode(Request $request, CustomCode $code): RedirectResponse
    {
        $data = $request->validate([
            'css' => ['nullable', 'string', 'max:100000'],
            'head' => ['nullable', 'string', 'max:50000'],
            'body' => ['nullable', 'string', 'max:50000'],
            'domains' => ['nullable', 'string', 'max:3000'],
        ]);
        $code->save(['css' => $data['css'] ?? '', 'head' => $data['head'] ?? '', 'body' => $data['body'] ?? '', 'domains' => $data['domains'] ?? ''], $request->user('admin'));
        AuditLog::record('updated', 'custom_code', null, __('admin.online.code_title'));

        return back()->with('success', __('admin.online.code_saved'));
    }

    public function restoreCode(Request $request, CodeVersion $version, CustomCode $code): RedirectResponse
    {
        $code->save($version->only(['css', 'head', 'body']) + ['domains' => $version->domains ?? []], $request->user('admin'));
        AuditLog::record('updated', 'custom_code', $version->id, __('admin.online.restored_label', ['id' => $version->id]));

        return back()->with('success', __('admin.online.code_restored'));
    }

    public function preferences(SettingsService $settings): View
    {
        return view('admin.online-store.preferences', ['values' => $settings->group('preferences'), 'productPage' => $settings->group('product_page')]);
    }

    public function savePreferences(Request $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'password_message.*' => ['nullable', 'string', 'max:300'],
            'product_short_description.*' => ['nullable', 'string', 'max:5000'],
            'product_dimensions.*' => ['nullable', 'string', 'max:20000'],
            'product_delivery_returns.*' => ['nullable', 'string', 'max:20000'],
        ]);
        $enabled = $request->boolean('password_enabled');
        $hash = filled($data['password'] ?? null) ? Hash::make($data['password']) : $settings->get('preferences.password_hash');

        if ($enabled && blank($hash)) {
            return back()->withErrors(['password' => __('admin.online.password_needed')]);
        }

        // Product page defaults: shown when a product has no text of its own.
        $rich = fn (string $field) => collect($data[$field] ?? [])->map(fn ($v) => \App\Support\RichText::clean($v))->filter()->all();
        $settings->setMany('product_page', [
            'short_description' => $rich('product_short_description'),
            'dimensions' => $rich('product_dimensions'),
            'delivery_returns' => $rich('product_delivery_returns'),
        ]);

        $settings->setMany('preferences', [
            'password_enabled' => $enabled,
            'password_hash' => $hash,
            'password_message' => array_filter($data['password_message'] ?? []),
            // The "Take the 1-minute tour" button and popups for shoppers (App\Services\Tours\StorefrontTour).
            'storefront_tour' => $request->boolean('storefront_tour'),
        ]);

        return back()->with('success', __('admin.settings.saved'));
    }
}
