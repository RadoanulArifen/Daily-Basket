<?php

namespace App\Services\Tours;

use App\Models\Admin;

/**
 * Each admin's guided-tour progress, and what the admin pages need to show it.
 *
 * Progress is kept on the admin account (admins.tour_state), so it follows them to another
 * computer. On a public demo (DEMO_MODE) every visitor signs in as the same account, so there it
 * is kept in the visitor's session instead: each visitor gets their own welcome and progress.
 *
 * State: welcome (bool: welcome screen dealt with), active (tour key or null), step (index of the
 * active tour's step), progress (tour => last step reached), done (finished tour keys).
 */
class AdminTours
{
    private const SESSION_KEY = 'admin_tours';

    private const LATER_KEY = 'admin_tours.welcome_later';

    /** @return array{welcome: bool, active: ?string, step: int, progress: array<string, int>, done: list<string>} */
    public function state(Admin $admin): array
    {
        $raw = config('store.demo') ? (array) session(self::SESSION_KEY, []) : (array) ($admin->tour_state ?? []);

        $active = AdminTourCatalog::has($raw['active'] ?? null) ? $raw['active'] : null;
        $progress = collect((array) ($raw['progress'] ?? []))
            ->filter(fn ($step, $tour) => AdminTourCatalog::has($tour))
            ->map(fn ($step, $tour) => max(0, min((int) $step, AdminTourCatalog::stepCount($tour) - 1)))
            ->all();

        return [
            'welcome' => (bool) ($raw['welcome'] ?? false),
            'active' => $active,
            'step' => $active ? ($progress[$active] ?? 0) : 0,
            'progress' => $progress,
            'done' => array_values(array_filter((array) ($raw['done'] ?? []), fn ($t) => AdminTourCatalog::has($t))),
        ];
    }

    public function save(Admin $admin, array $state): void
    {
        $clean = [
            'welcome' => (bool) $state['welcome'],
            'active' => $state['active'],
            'progress' => $state['progress'],
            'done' => array_values(array_unique($state['done'])),
        ];

        if (config('store.demo')) {
            session([self::SESSION_KEY => $clean]);

            return;
        }

        $admin->forceFill(['tour_state' => $clean])->save();
    }

    /** May this admin take the tour (its pages are in their role's areas)? */
    public function allowed(Admin $admin, string $tour): bool
    {
        return AdminTourCatalog::has($tour) && $admin->mayAccess(AdminTourCatalog::definitions()[$tour]['area']);
    }

    public function start(Admin $admin, string $tour, bool $resume = false): void
    {
        $state = $this->state($admin);
        $state['welcome'] = true;
        $state['active'] = $tour;
        $state['progress'][$tour] = $resume ? ($state['progress'][$tour] ?? 0) : 0;
        $this->save($admin, $state);
    }

    public function go(Admin $admin, string $tour, int $step): void
    {
        $state = $this->state($admin);
        $state['active'] = $tour;
        $state['progress'][$tour] = max(0, min($step, AdminTourCatalog::stepCount($tour) - 1));
        $this->save($admin, $state);
    }

    /** @return ?string the next tour to suggest, if any */
    public function finish(Admin $admin, string $tour): ?string
    {
        $state = $this->state($admin);
        $state['done'][] = $tour;
        unset($state['progress'][$tour]);
        $state['active'] = null;
        $this->save($admin, $state);

        return collect(AdminTourCatalog::keys())
            ->first(fn ($key) => ! in_array($key, $state['done'], true) && $this->allowed($admin, $key) && AdminTourCatalog::isAvailable($key));
    }

    /** Stop the tour for now; its progress is kept so "Continue" picks it up again. */
    public function pause(Admin $admin): void
    {
        $state = $this->state($admin);
        $state['active'] = null;
        $this->save($admin, $state);
    }

    public function reset(Admin $admin): void
    {
        $this->save($admin, ['welcome' => true, 'active' => null, 'progress' => [], 'done' => []]);
    }

    public function welcomeLater(): void
    {
        session([self::LATER_KEY => true]);
    }

    public function welcomeNever(Admin $admin): void
    {
        $state = $this->state($admin);
        $state['welcome'] = true;
        $this->save($admin, $state);
    }

    public function showWelcomeAgain(Admin $admin): void
    {
        session()->forget(self::LATER_KEY);
        $state = $this->state($admin);
        $state['welcome'] = false;
        $state['active'] = null;
        $this->save($admin, $state);
    }

    /**
     * Everything the admin layout shows: the tours this admin may take with their status, whether
     * the welcome screen opens, and the active tour's steps for the browser.
     *
     * @return array{tours: list<array<string, mixed>>, done: int, total: int, welcome: bool, active: ?array<string, mixed>}
     */
    public function overview(Admin $admin): array
    {
        $state = $this->state($admin);

        $tours = collect(AdminTourCatalog::definitions())
            ->filter(fn ($definition, $key) => $this->allowed($admin, $key))
            ->map(function ($definition, $key) use ($state) {
                $steps = count($definition['steps']);
                $status = match (true) {
                    in_array($key, $state['done'], true) && ! array_key_exists($key, $state['progress']) => 'done',
                    array_key_exists($key, $state['progress']) => 'started',
                    default => 'new',
                };

                return [
                    'key' => $key,
                    'name' => __("tours.admin.{$key}.name"),
                    'summary' => __("tours.admin.{$key}.summary"),
                    'steps' => $steps,
                    'minutes' => $definition['minutes'],
                    'status' => $status,
                    'reached' => ($state['progress'][$key] ?? 0) + 1,
                    'available' => AdminTourCatalog::isAvailable($key),
                ];
            })->values()->all();

        $active = $state['active'] && $this->allowed($admin, $state['active']) && AdminTourCatalog::isAvailable($state['active'])
            ? ['key' => $state['active'], 'name' => __("tours.admin.{$state['active']}.name"), 'step' => $state['step'], 'steps' => AdminTourCatalog::steps($state['active'])]
            : null;

        return [
            'tours' => $tours,
            'done' => collect($tours)->where('status', 'done')->count(),
            'total' => count($tours),
            'welcome' => $tours !== [] && ! $state['welcome'] && ! $active && ! session(self::LATER_KEY),
            'active' => $active,
        ];
    }
}
