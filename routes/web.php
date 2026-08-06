<?php

use App\Http\Controllers\AiGenerationController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
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

require __DIR__.'/auth.php';
