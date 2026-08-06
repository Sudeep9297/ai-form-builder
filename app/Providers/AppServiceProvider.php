<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '') {
            URL::useOrigin($appUrl);
            URL::useAssetOrigin($appUrl);

            Vite::createAssetPathsUsing(fn (string $path) => $appUrl.'/'.ltrim($path, '/'));

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        Vite::prefetch(concurrency: 3);
    }
}
