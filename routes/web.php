<?php

use App\Http\Controllers\AiGenerationController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/f/{token}', [PublicFormController::class, 'show'])->name('public.forms.show');
Route::post('/f/{token}', [PublicFormController::class, 'submit'])->middleware('throttle:20,1')->name('public.forms.submit');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/dashboard', '/forms')->name('dashboard');
    Route::resource('forms', FormController::class);
    Route::post('/forms/{form}/rollback/{version}', [FormController::class, 'rollback'])->name('forms.rollback');
    Route::get('/forms/{form}/submissions.csv', [FormController::class, 'exportCsv'])->name('forms.submissions.csv');
    Route::post('/ai-generations', [AiGenerationController::class, 'store'])->name('ai-generations.store');
    Route::get('/ai-generations/{generation}', [AiGenerationController::class, 'show'])->name('ai-generations.show');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/imports/{importBatch}', [ImportController::class, 'show'])->name('imports.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

if (config('app.debug')) {
    Route::get('/debug-request', function (Request $request) {
        return response()->json([
            'host' => $request->getHost(),
            'http_host' => $request->server('HTTP_HOST'),
            'server_name' => $request->server('SERVER_NAME'),
            'scheme' => $request->getScheme(),
            'full_url' => $request->fullUrl(),
            'url' => url('/'),
            'app_url' => config('app.url'),
            'forwarded' => [
                'host' => $request->headers->get('x-forwarded-host'),
                'proto' => $request->headers->get('x-forwarded-proto'),
                'port' => $request->headers->get('x-forwarded-port'),
                'prefix' => $request->headers->get('x-forwarded-prefix'),
            ],
        ]);
    });
}

require __DIR__.'/auth.php';
