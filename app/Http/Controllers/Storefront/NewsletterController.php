<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Join the list": newsletter sign-up (spam trap + rate limit; re-subscribing is fine). */
class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Bots fill the hidden "website" field; pretend it worked.
        if (filled($request->input('website'))) {
            return $this->done($request);
        }

        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        Subscriber::query()->updateOrCreate(
            ['email' => mb_strtolower(trim($data['email']))],
            ['locale' => app()->getLocale(), 'source' => (string) $request->input('source', 'footer'), 'unsubscribed_at' => null],
        );

        return $this->done($request);
    }

    private function done(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('store.newsletter.thanks');

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('store_toast', $message);
    }
}
