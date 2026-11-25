<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** The plain HTML landing page in resources/landing, served at /landing/. */
class LandingPageTest extends TestCase
{
    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();
        // Work on a copy so the tests never touch the real page.
        $this->backup = storage_path('framework/testing/landing-backup');
        File::deleteDirectory($this->backup);
        File::copyDirectory(resource_path('landing'), $this->backup);
        File::cleanDirectory(resource_path('landing'));
        File::put(resource_path('landing/index.html'), '<!doctype html><html><head><link rel="stylesheet" href="assets/site.css"></head><body><h1>Hello landing</h1><script>window.ok = 1;</script></body></html>');
        File::ensureDirectoryExists(resource_path('landing/assets/img'));
        File::put(resource_path('landing/assets/site.css'), 'body{color:red}');
        File::put(resource_path('landing/assets/img/logo.svg'), '<svg xmlns="http://www.w3.org/2000/svg"/>');
        File::put(resource_path('landing/.env'), 'SECRET=1');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(resource_path('landing'));
        File::copyDirectory($this->backup, resource_path('landing'));
        File::deleteDirectory($this->backup);
        parent::tearDown();
    }

    /** The test client drops a trailing slash, so send "/landing/" as a browser would. */
    private function raw(string $uri): TestResponse
    {
        return TestResponse::fromBaseResponse($this->app->make(Kernel::class)->handle(Request::create($uri)));
    }

    public function test_the_page_is_served_at_landing_slash(): void
    {
        $page = $this->raw('/landing/')->assertOk()->assertSee('Hello landing');
        $this->assertStringStartsWith('text/html', $page->headers->get('Content-Type'));
        $this->assertStringNotContainsString('nonce-', (string) $page->headers->get('Content-Security-Policy'));   // its own scripts run
        $this->assertSame('noindex', $page->headers->get('X-Robots-Tag'));
    }

    public function test_short_addresses_redirect_so_relative_links_work(): void
    {
        $this->get('/landing')->assertRedirect(url('landing').'/');
        $this->get('/landing.html')->assertRedirect(url('landing').'/');
        $this->get('/landing/index.html')->assertOk()->assertSee('Hello landing');
    }

    public function test_its_files_are_served_with_the_right_type(): void
    {
        $css = $this->get('/landing/assets/site.css')->assertOk();
        $this->assertStringStartsWith('text/css', $css->headers->get('Content-Type'));
        $this->assertStringStartsWith('image/svg+xml', $this->get('/landing/assets/img/logo.svg')->assertOk()->headers->get('Content-Type'));
        $this->get('/landing/assets/missing.png')->assertNotFound();
        $this->raw('/landing/assets/img')->assertRedirect(url('landing/assets/img').'/');   // a folder with no index.html
        $this->raw('/landing/assets/img/')->assertNotFound();
    }

    public function test_hidden_files_and_paths_outside_the_folder_are_refused(): void
    {
        $this->get('/landing/.env')->assertNotFound();
        $this->raw('/landing/../../.env')->assertNotFound();
        $this->raw('/landing/%2e%2e/%2e%2e/composer.json')->assertNotFound();
        $this->raw('/landing/assets/%2e%2e/%2e%2e/%2e%2e/composer.json')->assertNotFound();
    }
}
