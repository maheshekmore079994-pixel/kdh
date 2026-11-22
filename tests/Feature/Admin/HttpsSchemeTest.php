<?php

namespace Tests\Feature\Admin;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsSchemeTest extends TestCase
{
    use RefreshDatabase;

    private function bootAs(string $env, string $url): string
    {
        $this->app['env'] = $env;
        config(['app.url' => $url]);
        URL::forceScheme(null);
        (new AppServiceProvider($this->app))->boot();

        return route('setup');
    }

    public function test_desktop_production_on_http_keeps_http_links(): void
    {
        $this->assertStringStartsWith('http://', $this->bootAs('production', 'http://127.0.0.1:8100'));
    }

    public function test_hosted_production_on_https_forces_https_links(): void
    {
        $this->assertStringStartsWith('https://', $this->bootAs('production', 'https://kdh.example.in'));
    }
}
