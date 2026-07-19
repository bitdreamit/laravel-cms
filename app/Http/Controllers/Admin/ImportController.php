<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index()
    {
        $jobs = ImportJob::where('tenant_id', tenant('id'))->orderByDesc('created_at')->paginate(20);
        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_type' => 'required|in:wordpress,csv,json,xml',
            'collection_handle' => 'required|string',
            'source_file' => 'required|file|max:51200',
            'config' => 'nullable|array',
        ]);

        $file = $request->file('source_file');
        $path = $file->storeAs('imports', Str::uuid() . '.' . $file->getClientOriginalExtension());

        $job = ImportJob::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'source_type' => $data['source_type'],
            'source_file' => $path,
            'collection_handle' => $data['collection_handle'],
            'status' => 'pending',
            'user_id' => auth()->id(),
            'config' => $data['config'] ?? [],
        ]);

        // Dispatch the import job
        \App\Jobs\ProcessImport::dispatch($job->id);

        return response()->json($job, 201);
    }

    public function show(string $id)
    {
        $job = ImportJob::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($job);
    }

    public function destroy(string $id)
    {
        $job = ImportJob::where('tenant_id', tenant('id'))->findOrFail($id);
        $job->delete();
        return response()->noContent();
    }
}
