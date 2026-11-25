<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Every admin route that changes data must refuse a visitor who is not signed in. */
class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_admin_route_that_changes_data_is_open_to_guests(): void
    {
        $open = [];

        foreach (Route::getRoutes() as $route) {
            $methods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            $uri = $route->uri();

            if (! $methods || ! str_starts_with($uri, config('store.admin_path'))) {
                continue;
            }

            // Signing in and password resets are meant to be reachable, and so is the
            // language switch on the login screen — it writes only to the session unless
            // an admin is already signed in.
            if (preg_match('#(login|logout|forgot-password|reset-password|locale)#', $uri)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            // Laravel resolves the "auth:admin" alias to the class, so accept either form.
            $guarded = collect($middleware)->contains(
                fn ($m) => is_string($m) && (str_starts_with($m, 'auth:admin') || str_contains($m, 'Authenticate:admin'))
            );

            if (! $guarded) {
                $open[] = reset($methods).' /'.$uri;
            }
        }

        $this->assertSame([], $open, 'These admin routes change data without requiring a signed-in admin.');
    }

    public function test_a_guest_is_turned_away_from_the_admin(): void
    {
        $this->post('/admin/products')->assertRedirect();
        $this->assertGuest('admin');
    }
}
