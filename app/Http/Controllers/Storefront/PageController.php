<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('storefront.pages.show', compact('page'));
    }

    public function contact(Request $request, string $slug): RedirectResponse
    {
        $page = Page::query()->where('slug', $slug)->where('is_active', true)->where('show_contact_form', true)->firstOrFail();

        // Bots fill in every field, including this hidden one.
        if ($request->filled('website')) {
            return back()->with('contact_sent', true);
        }

        $data = $request->validate([
            'g-recaptcha-response' => [new \App\Rules\Recaptcha],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], [
            'name' => __('store.checkout.full_name'),
            'email' => __('store.auth.email'),
            'message' => __('store.contact.message'),
        ]);

        try {
            if ($to = settings()->get('general.store_email')) {
                Mail::to($to)->send(new ContactMessage($data));
            }

        Mail::to($data['email'])->send(new \App\Mail\CustomerMessage(
            event: 'contact',
            vars: ['{name}' => \Illuminate\Support\Str::before($data['name'], ' ')],
            details: [__('store.contact.message') => $data['message']],
            lang: app()->getLocale(),
        ));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['message' => __('store.contact.failed')]);
        }

        return redirect(store_route('pages.show', ['slug' => $page->slug]))->with('contact_sent', true);
    }
}
