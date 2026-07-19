<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function submit(Request $request, string $formHandle)
    {
        $form = Form::where('tenant_id', tenant('id'))
            ->where('handle', $formHandle)
            ->where('is_active', true)
            ->firstOrFail();

        // Honeypot check
        if ($form->honeypot_field && $request->filled($form->honeypot_field)) {
            return response()->json(['message' => 'Submission blocked.'], 422);
        }

        // Validate based on form fields config
        $rules = $this->buildValidationRules($form);
        $data = $request->validate($rules);

        // Capture visitor info
        $visitorId = $request->cookie(config('personalization.visitor.cookie_name'));

        $submission = FormSubmission::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'form_id' => $form->id,
            'visitor_id' => $visitorId,
            'user_id' => auth()->id(),
            'data' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        // V4: Run lead scoring if configured
        if (tenant_has_feature('form_analytics')) {
            $scoringRule = \App\Models\Tenant\FormLeadScoringRule::where('form_id', $form->id)->first();
            if ($scoringRule) {
                app(\App\Domain\Form\Actions\ScoreLead::class)->execute($submission, $scoringRule);
            }
        }

        // Fire event for email notifications and webhooks
        event(new \App\Domain\Content\Events\FormSubmitted($submission, $form));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $form->success_message ?? 'Form submitted successfully.',
                'submission_id' => $submission->id,
            ]);
        }

        if ($form->redirect_url) {
            return redirect()->away($form->redirect_url)->with('success', $form->success_message);
        }

        return back()->with('success', $form->success_message ?? 'Form submitted successfully.');
    }

    protected function buildValidationRules(Form $form): array
    {
        $rules = [];
        foreach ($form->fields ?? [] as $field) {
            $fieldRules = [];
            if (! empty($field['required'])) $fieldRules[] = 'required';
            if (($field['type'] ?? 'text') === 'email') $fieldRules[] = 'email';
            if (! empty($field['validation'])) {
                $fieldRules = array_merge($fieldRules, explode('|', $field['validation']));
            }
            $rules[$field['handle'] ?? $field['name']] = implode('|', $fieldRules) ?: 'nullable';
        }
        return $rules;
    }
}
