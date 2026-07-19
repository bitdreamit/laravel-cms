<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::where('tenant_id', tenant('id'))->paginate(20);
        return response()->json($forms);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'handle' => 'required|string|max:100',
            'description' => 'nullable|string',
            'fields' => 'nullable|array',
            'email_recipients' => 'nullable|array',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $form = Form::create(array_merge($data, [
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
        ]));

        return response()->json($form, 201);
    }

    public function show(string $id)
    {
        $form = Form::where('tenant_id', tenant('id'))->findOrFail($id);
        return response()->json($form);
    }

    public function update(Request $request, string $id)
    {
        $form = Form::where('tenant_id', tenant('id'))->findOrFail($id);
        $form->update($request->all());
        return response()->json($form);
    }

    public function destroy(string $id)
    {
        $form = Form::where('tenant_id', tenant('id'))->findOrFail($id);
        $form->delete();
        return response()->noContent();
    }

    public function submissions(string $id, Request $request)
    {
        $form = Form::where('tenant_id', tenant('id'))->findOrFail($id);

        $submissions = FormSubmission::where('tenant_id', tenant('id'))
            ->where('form_id', $form->id)
            ->when($request->input('qualified_only'), fn($q) => $q->where('is_qualified', true))
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return response()->json($submissions);
    }

    public function showSubmission(string $id, string $submissionId)
    {
        $submission = FormSubmission::where('tenant_id', tenant('id'))
            ->where('form_id', $id)
            ->where('id', $submissionId)
            ->firstOrFail();

        return response()->json($submission);
    }

    public function assignSubmission(Request $request, string $id, string $submissionId)
    {
        $submission = FormSubmission::where('tenant_id', tenant('id'))
            ->where('form_id', $id)
            ->where('id', $submissionId)
            ->firstOrFail();

        $data = $request->validate(['assigned_to' => 'required|uuid']);
        $submission->update([
            'assigned_to' => $data['assigned_to'],
            'assigned_at' => now(),
        ]);

        return response()->json($submission);
    }
}
