<?php

use Illuminate\Support\Facades\Route;

it('generates https urls behind a trusted proxy', function () {
    config(['app.url' => 'https://ai-form-builder-web-production.up.railway.app']);
    (new App\Providers\AppServiceProvider($this->app))->boot();

    Route::get('/__proxy-url-check', fn () => response()->json([
        'secure' => request()->isSecure(),
        'url' => url('/'),
        'asset' => asset('build/manifest.json'),
    ]));

    $this->withServerVariables([
        'REMOTE_ADDR' => '10.0.0.10',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'ai-form-builder-web-production.up.railway.app',
        'HTTP_X_FORWARDED_PORT' => '443',
    ])
        ->get('/__proxy-url-check')
        ->assertOk()
        ->assertJsonPath('secure', true)
        ->assertJsonPath('url', 'https://ai-form-builder-web-production.up.railway.app')
        ->assertJsonPath('asset', 'https://ai-form-builder-web-production.up.railway.app/build/manifest.json');
});

it('renders Vite and Ziggy urls from the configured app url', function () {
    config(['app.url' => 'https://ai-form-builder-web-production.up.railway.app']);
    (new App\Providers\AppServiceProvider($this->app))->boot();
    Tighten\Ziggy\BladeRouteGenerator::$generated = false;

    $response = $this->withServerVariables([
        'HTTP_HOST' => '127.0.0.1:8080',
        'SERVER_NAME' => '127.0.0.1',
        'SERVER_PORT' => '8080',
        'REMOTE_ADDR' => '127.0.0.1',
    ])->get('/');

    $response->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('https:\/\/ai-form-builder-web-production.up.railway.app');
    expect($html)->toContain('https://ai-form-builder-web-production.up.railway.app/build/assets/');
    expect($html)->not->toContain('https://127.0.0.1:8080');
    expect($html)->not->toContain('http://127.0.0.1:8080');
});
