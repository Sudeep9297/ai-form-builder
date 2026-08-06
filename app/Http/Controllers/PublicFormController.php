<?php

namespace App\Http\Controllers;

use App\Events\FormSubmitted;
use App\Http\Requests\SubmitPublicFormRequest;
use App\Models\Form;
use App\Models\Submission;
use App\Services\FormService;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PublicFormController extends Controller
{
    public function show(string $token)
    {
        $form = Form::where('public_token', $token)->where('is_published', true)->firstOrFail();

        return Inertia::render('Public/Fill', ['form' => [
            'title' => $form->title,
            'description' => $form->description,
            'schema' => $form->schema,
            'token' => $form->public_token,
        ]]);
    }

    public function submit(SubmitPublicFormRequest $request, string $token, FormService $forms)
    {
        $form = Form::where('public_token', $token)->where('is_published', true)->firstOrFail();
        $answers = $request->validated('answers');
        $validated = Validator::make($answers, $forms->compiledRules($form))->validate();
        foreach ($validated as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $validated[$key] = $value->store('submissions/'.$form->id, 'public');
            }
        }

        $submission = Submission::create([
            'tenant_id' => $form->tenant_id,
            'form_id' => $form->id,
            'form_version' => $form->version,
            'payload' => $validated,
            'respondent_email' => $validated['email'] ?? $validated['email_address'] ?? null,
            'respondent_name' => $validated['name'] ?? $validated['full_name'] ?? null,
            'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'completed_at' => now(),
        ]);

        event(new FormSubmitted($submission));

        return back()->with('success', 'Submitted successfully.');
    }
}
