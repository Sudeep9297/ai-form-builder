<?php

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/forms/{form}/submissions', function (Request $request, Form $form) {
    abort_unless($form->tenant_id === $request->user()->tenant_id, 404);

    return $form->submissions()
        ->latest()
        ->paginate(min((int) $request->query('per_page', 25), 100));
})->name('api.forms.submissions');
