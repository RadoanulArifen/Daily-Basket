<?php

namespace App\Http\Controllers\Admin\Help;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Tours\AdminTourCatalog;
use App\Services\Tours\AdminTours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guided tours: start, move, finish, pause and reset — the signed-in admin's own progress only.
 * Called by the tour popups (JSON) and by the welcome screen and ? menu (forms). Open to every
 * admin (AdminRole::OPEN_TO_ALL), but a tour can only be started when its pages are in the
 * admin's role areas.
 */
class TourController extends AdminController
{
    public function update(Request $request, AdminTours $tours): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['start', 'resume', 'go', 'finish', 'pause', 'reset', 'welcome_later', 'welcome_never', 'welcome_show'])],
            'tour' => ['nullable', 'string', Rule::in(AdminTourCatalog::keys())],
            'step' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);
        $admin = $request->user('admin');
        $tour = $data['tour'] ?? null;

        if (in_array($data['action'], ['start', 'resume', 'go', 'finish'], true)) {
            abort_unless($tour !== null && $tours->allowed($admin, $tour), 403);
            abort_unless(AdminTourCatalog::isAvailable($tour), 409);
        }

        $toast = null;
        $next = null;
        $redirect = null;

        switch ($data['action']) {
            case 'start':
            case 'resume':
                $tours->start($admin, $tour, $data['action'] === 'resume');
                $step = AdminTourCatalog::steps($tour)[$tours->state($admin)['step']] ?? null;
                $redirect = $step['url'] ?? null;
                break;
            case 'go':
                $tours->go($admin, $tour, (int) ($data['step'] ?? 0));
                break;
            case 'finish':
                $next = $tours->finish($admin, $tour);
                $toast = __('tours.finished', ['name' => __("tours.admin.{$tour}.name")]);
                break;
            case 'pause':
                $tours->pause($admin);
                $toast = __('tours.paused');
                break;
            case 'reset':
                $tours->reset($admin);
                $toast = __('tours.reset_done');
                break;
            case 'welcome_later':
                $tours->welcomeLater();
                break;
            case 'welcome_never':
                $tours->welcomeNever($admin);
                break;
            case 'welcome_show':
                $tours->showWelcomeAgain($admin);
                break;
        }

        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'ok' => true,
                'toast' => $toast,
                'next' => $next ? ['key' => $next, 'name' => __("tours.admin.{$next}.name")] : null,
            ], fn ($v) => $v !== null));
        }

        $response = $redirect ? redirect()->to($redirect) : back();

        return $toast ? $response->with('success', $toast) : $response;
    }
}
