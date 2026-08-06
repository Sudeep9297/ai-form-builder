<?php

use App\Http\Controllers\Api\FormSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->get('/forms/{form}/submissions', [FormSubmissionController::class, 'index'])
    ->name('api.forms.submissions');
