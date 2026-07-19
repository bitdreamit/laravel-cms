<?php

namespace App\Domain\Personalization\Conditions;

use Illuminate\Support\Facades\DB;

class SubmittedFormCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $formHandle = $config['form'] ?? null;
        $visitorId = $context->getVisitorId();
        $userId = $context->getUserId();

        if (! $visitorId && ! $userId) return false;

        $query = DB::table('form_submissions')
            ->where('tenant_id', $context->getTenantId());

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($visitorId) {
            $query->where('visitor_id', $visitorId);
        }

        if ($formHandle) {
            $query->join('forms', 'forms.id', '=', 'form_submissions.form_id')
                  ->where('forms.handle', $formHandle);
        }

        return $query->exists();
    }
}
