<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportFormRequest;
use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(ImportFormRequest $request)
    {
        $file = $request->file('source');
        $path = $file->store('imports');

        $batch = ImportBatch::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'status' => 'queued',
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
        ]);

        ProcessImportBatch::dispatch($batch);

        return back()->with('importBatchId', $batch->id);
    }

    public function show(Request $request, ImportBatch $importBatch)
    {
        abort_unless($importBatch->tenant_id === $request->user()->tenant_id, 404);

        return response()->json($importBatch);
    }
}
