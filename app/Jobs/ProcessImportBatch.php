<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\ImportParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessImportBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public ImportBatch $batch)
    {
        $this->onQueue('imports');
    }

    public function handle(ImportParserService $parser): void
    {
        $this->batch->update(['status' => 'running']);

        try {
            $absolute = Storage::path($this->batch->path);
            $extension = pathinfo($this->batch->original_name, PATHINFO_EXTENSION);
            $result = $parser->parse($absolute, $extension);

            $this->batch->update([
                'status' => 'ready_for_mapping',
                'detected_schema' => $result['schema'],
                'mapping' => ['fields' => $result['schema']['steps'][0]['fields'] ?? []],
                'warnings' => $result['warnings'] ?? [],
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->batch->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }
}
