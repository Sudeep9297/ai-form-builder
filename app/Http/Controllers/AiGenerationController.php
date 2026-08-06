<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartAiGenerationRequest;
use App\Jobs\GenerateFormWithAi;
use App\Models\AiGeneration;
use App\Models\Form;
use Illuminate\Http\Request;

class AiGenerationController extends Controller
{
    public function store(StartAiGenerationRequest $request)
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

        GenerateFormWithAi::dispatch($generation);

        return back()->with('aiGenerationId', $generation->id);
    }

    public function show(Request $request, AiGeneration $generation)
    {
        abort_unless($generation->tenant_id === $request->user()->tenant_id, 404);

        return response()->json($generation);
    }
}
