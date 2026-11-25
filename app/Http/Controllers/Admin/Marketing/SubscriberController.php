<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Newsletter sign-ups: who is on the list, and a CSV to take elsewhere. */
class SubscriberController extends AdminController
{
    public function index(Request $request): View
    {
        $showing = $request->query('status') === 'unsubscribed' ? 'unsubscribed' : 'subscribed';
        $term = trim((string) $request->query('q'));

        $subscribers = Subscriber::query()
            ->when($showing === 'subscribed', fn ($q) => $q->whereNull('unsubscribed_at'), fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->when($term !== '', fn ($q) => $q->where('email', 'like', "%{$term}%"))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.marketing.subscribers.index', [
            'subscribers' => $subscribers,
            'showing' => $showing,
            'term' => $term,
            'counts' => [
                'subscribed' => Subscriber::query()->whereNull('unsubscribed_at')->count(),
                'unsubscribed' => Subscriber::query()->whereNotNull('unsubscribed_at')->count(),
            ],
        ]);
    }

    /** A CSV of the people currently on the list, for a mail tool or a spreadsheet. */
    public function export(Request $request): StreamedResponse
    {
        $showing = $request->query('status') === 'unsubscribed' ? 'unsubscribed' : 'subscribed';
        $name = 'subscribers-'.$showing.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($showing) {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");   // so Excel reads accents correctly
            fputcsv($out, ['email', 'locale', 'source', 'subscribed_at', 'unsubscribed_at']);

            Subscriber::query()
                ->when($showing === 'subscribed', fn ($q) => $q->whereNull('unsubscribed_at'), fn ($q) => $q->whereNotNull('unsubscribed_at'))
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [$row->email, $row->locale, $row->source, $row->created_at?->toDateString(), $row->unsubscribed_at?->toDateString()]);
                    }
                });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', __('admin.subscribers.removed'));
    }
}
