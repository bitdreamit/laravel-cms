<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\FormAnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormAnalyticsController extends Controller
{
    public function track(Request $request, string $formId)
    {
        $request->validate([
            'event_type' => 'required|in:view,start,field_focus,field_blur,field_change,submit_attempt,submit_success,submit_error,abandon',
            'field_handle' => 'nullable|string',
            'event_data' => 'nullable|array',
        ]);

        $visitorId = $request->cookie(config('personalization.visitor.cookie_name'));
        if (! $visitorId) {
            $visitorId = Str::uuid()->toString();
            cookie()->queue(config('personalization.visitor.cookie_name'), $visitorId, config('personalization.visitor.cookie_minutes'));
        }

        FormAnalyticsEvent::create([
            'id' => Str::uuid(),
            'tenant_id' => tenant('id'),
            'form_id' => $formId,
            'visitor_id' => $visitorId,
            'event_type' => $request->input('event_type'),
            'field_handle' => $request->input('field_handle'),
            'event_data' => $request->input('event_data'),
            'page_url' => $request->header('referer', ''),
            'occurred_at' => now(),
        ]);

        return response()->json(['tracked' => true]);
    }
}
