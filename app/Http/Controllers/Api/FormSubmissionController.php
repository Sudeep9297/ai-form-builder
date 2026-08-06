<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;

class FormSubmissionController extends Controller
{
    public function index(Request $request, Form $form)
    {
        abort_unless($form->tenant_id === $request->user()->tenant_id, 404);

        return $form->submissions()
            ->latest()
            ->paginate(min((int) $request->query('per_page', 25), 100));
    }
}
