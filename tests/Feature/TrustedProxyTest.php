<?php

use Illuminate\Support\Facades\Route;

it('generates https urls behind a trusted proxy', function () {
    config(['app.url' => 'https://ai-form-builder-web-production.up.railway.app']);

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
