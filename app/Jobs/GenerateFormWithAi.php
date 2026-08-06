<?php

namespace App\Jobs;

use App\Exceptions\LlmProviderNotConfiguredException;
use App\Models\AiGeneration;
use App\Services\AiFormService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateFormWithAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public AiGeneration $generation)
    {
        $this->onQueue('ai');
    }

    public function handle(AiFormService $ai): void
    {
        $this->generation->update(['status' => 'running', 'started_at' => now()]);

        try {
            $existing = $this->generation->form?->schema;
            $result = $ai->generate($this->generation->prompt, $existing);

            $this->generation->update([
                'status' => 'completed',
                'model' => $result['model'],
                'prompt_tokens' => (int) $result['prompt_tokens'],
                'completion_tokens' => (int) $result['completion_tokens'],
                'latency_ms' => (int) $result['latency_ms'],
                'result_schema' => $result['schema'],
                'finished_at' => now(),
            ]);
        } catch (LlmProviderNotConfiguredException $exception) {
            $this->generation->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->generation->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }
}
