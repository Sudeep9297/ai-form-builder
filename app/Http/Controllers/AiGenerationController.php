<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartAiGenerationRequest;
use App\Jobs\GenerateFormWithAi;
use App\Models\AiGeneration;
use App\Models\Form;
use App\Services\AiFormService;
use Illuminate\Http\Request;

class AiGenerationController extends Controller
{
    public function store(StartAiGenerationRequest $request, AiFormService $ai)
    {
        $data = $request->validated();
        $form = isset($data['form_id'])
            ? Form::where('tenant_id', $request->user()->tenant_id)->findOrFail($data['form_id'])
            : null;

        $generation = AiGeneration::create([
            'tenant_id' => $request->user()->tenant_id,
            'form_id' => $form?->id,
            'user_id' => $request->user()->id,
            'mode' => $data['mode'],
            'prompt' => $data['prompt'],
            'status' => 'queued',
        ]);

        if (! $ai->hasConfiguredProvider()) {
            $message = 'No LLM provider is configured. Add an LLM API key to enable AI form generation.';
            $generation->update([
                'status' => 'failed',
                'error' => $message,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'generation' => $generation->fresh(),
                ], 503);
            }

            return back()->with('aiGenerationId', $generation->id);
        }

        GenerateFormWithAi::dispatch($generation);

        return back()->with('aiGenerationId', $generation->id);
    }

    public function show(Request $request, AiGeneration $generation)
    {
        abort_unless($generation->tenant_id === $request->user()->tenant_id, 404);

        return response()->json($generation);
    }
}
