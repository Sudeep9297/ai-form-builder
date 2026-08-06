<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRequest;
use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormSchemaService;
use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $forms = Form::where('tenant_id', $request->user()->tenant_id)
            ->withCount('submissions')
            ->latest()
            ->paginate(12)
            ->through(fn (Form $form) => $this->payload($form));

        return Inertia::render('Forms/Index', ['forms' => $forms]);
    }

    public function create(FormSchemaService $schemas)
    {
        return Inertia::render('Forms/Builder', [
            'form' => null,
            'schema' => $schemas->defaultSchema('Untitled form'),
            'versions' => [],
            'submissions' => ['data' => []],
            'publicUrl' => null,
        ]);
    }

    public function store(StoreFormRequest $request, FormService $forms)
    {
        $form = $forms->create($request->user(), $request->validated());

        return redirect()->route('forms.edit', $form)->with('success', 'Form created.');
    }

    public function edit(Request $request, Form $form)
    {
        $this->authorizeTenant($request, $form);

        return Inertia::render('Forms/Builder', [
            'form' => $this->payload($form),
            'schema' => $form->schema,
            'versions' => $form->versions()->latest('version')->get(),
            'submissions' => $form->submissions()
                ->when($request->string('search')->toString(), function ($query, string $search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('respondent_email', 'like', "%{$search}%")
                            ->orWhere('respondent_name', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10),
            'publicUrl' => route('public.forms.show', $form->public_token),
        ]);
    }

    public function show(Request $request, Form $form)
    {
        $this->authorizeTenant($request, $form);

        return redirect()->route('forms.edit', $form);
    }

    public function update(StoreFormRequest $request, Form $form, FormService $forms)
    {
        $this->authorizeTenant($request, $form);
        $forms->update($form, $request->user(), $request->validated());

        return back()->with('success', 'Form saved.');
    }

    public function destroy(Request $request, Form $form)
    {
        $this->authorizeTenant($request, $form);
        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }

    public function rollback(Request $request, Form $form, FormVersion $version, FormService $forms)
    {
        $this->authorizeTenant($request, $form);
        abort_unless($version->form_id === $form->id, 404);
        $forms->rollback($form, $version, $request->user());

        return back()->with('success', 'Rolled back to version '.$version->version.'.');
    }

    public function exportCsv(Request $request, Form $form)
    {
        $this->authorizeTenant($request, $form);
        $headers = collect($form->schema['steps'] ?? [])
            ->flatMap(fn ($step) => $step['fields'] ?? [])
            ->where('type', '!=', 'section')
            ->pluck('key')
            ->prepend('submitted_at')
            ->all();

        $callback = function () use ($headers, $form) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($form->submissions()->latest()->cursor() as $submission) {
                $line = [$submission->created_at->toIso8601String()];
                foreach (array_slice($headers, 1) as $key) {
                    $value = $submission->payload[$key] ?? '';
                    $line[] = is_array($value) ? implode('; ', $value) : $value;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        };

        return Response::streamDownload($callback, $form->slug.'-submissions.csv', ['Content-Type' => 'text/csv']);
    }

    private function payload(Form $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'slug' => $form->slug,
            'description' => $form->description,
            'schema' => $form->schema,
            'settings' => $form->settings,
            'version' => $form->version,
            'is_published' => $form->is_published,
            'submissions_count' => $form->submissions_count ?? null,
            'updated_at' => $form->updated_at?->toDateTimeString(),
        ];
    }

    private function authorizeTenant(Request $request, Form $form): void
    {
        abort_unless($form->tenant_id === $request->user()->tenant_id, 404);
    }
}
