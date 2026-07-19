<?php

namespace App\Domain\Personalization\Conditions;

use Illuminate\Support\Facades\DB;

class ViewedEntryCondition implements ConditionInterface
{
    public function matches(array $config, Context $context): bool
    {
        $visitorId = $context->getVisitorId();
        if (! $visitorId) return false;

        $entryId = $config['entry_id'] ?? null;
        $collectionHandle = $config['collection'] ?? null;

        $query = DB::table('visitor_session_views')
            ->where('visitor_id', $visitorId)
            ->where('tenant_id', $context->getTenantId());

        if ($entryId) {
            $query->where('entry_id', $entryId);
        }

        if ($collectionHandle) {
            $query->join('entries', 'entries.id', '=', 'visitor_session_views.entry_id')
                  ->where('entries.collection_handle', $collectionHandle);
        }

        return $query->exists();
    }
}
