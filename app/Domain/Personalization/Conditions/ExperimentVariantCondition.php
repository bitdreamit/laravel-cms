<?php

namespace App\Domain\Personalization\Conditions;

use App\Models\Tenant\ExperimentAssignment;

class ExperimentVariantCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $experimentHandle = $config['experiment'] ?? null;
        $variantHandle = $config['variant'] ?? null;

        if (! $experimentHandle) return false;

        $visitorId = $context->getVisitorId();
        if (! $visitorId) return false;

        $assignment = ExperimentAssignment::whereHas('experiment', function ($q) use ($experimentHandle, $context) {
            $q->where('handle', $experimentHandle)
              ->where('tenant_id', $context->getTenantId());
        })
            ->where('visitor_id', $visitorId)
            ->first();

        if (! $assignment) return false;

        if ($variantHandle) {
            return $assignment->variant->handle === $variantHandle;
        }

        return true;
    }
}
