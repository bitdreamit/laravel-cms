<?php

namespace App\Domain\Personalization\Services;

use App\Domain\Personalization\Conditions\Context;
use App\Models\Tenant\Segment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SegmentEvaluator
{
    /**
     * Evaluate a single segment against the current visitor context.
     */
    public function evaluate(Segment $segment, Request $request): bool
    {
        $context = new Context(
            request: $request,
            visitorId: $request->cookie(config('personalization.visitor.cookie_name')),
            userId: auth()->id(),
            tenantId: tenant('id'),
        );

        return $this->evaluateRules($segment->rules, $context);
    }

    /**
     * Evaluate all segments for the current visitor. Results cached per visitor.
     */
    public function evaluateAll(string $tenantId, Request $request): Collection
    {
        $visitorId = $request->cookie(config('personalization.visitor.cookie_name'));
        $cacheKey = "segments:{$tenantId}:{$visitorId}:" . md5($request->fullUrl());

        return Cache::remember($cacheKey, now()->addMinutes((int) config('personalization.session.cache_ttl_minutes', 60)), function () use ($tenantId, $request) {
            $segments = Segment::where('tenant_id', $tenantId)->get();
            return $segments->filter(fn($segment) => $this->evaluate($segment, $request));
        });
    }

    /**
     * Evaluate a rules JSON structure (supports AND/OR/NOT logic).
     */
    public function evaluateRules(array $rules, Context $context): bool
    {
        $logic = $rules['logic'] ?? 'and';
        $conditions = $rules['conditions'] ?? [];

        if (empty($conditions)) return true;

        $results = array_map(fn($condition) => $this->evaluateCondition($condition, $context), $conditions);

        return match (strtolower($logic)) {
            'and' => ! in_array(false, $results, true),
            'or' => in_array(true, $results, true),
            'not' => ! $results[0],
            default => false,
        };
    }

    protected function evaluateCondition(array $condition, Context $context): bool
    {
        $type = $condition['type'] ?? null;
        $conditionClass = config("personalization.conditions.{$type}");

        if (! $conditionClass || ! class_exists($conditionClass)) {
            return false;
        }

        $conditionInstance = app($conditionClass);
        return $conditionInstance->matches($condition, $context);
    }
}
