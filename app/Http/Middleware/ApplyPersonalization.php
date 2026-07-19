<?php

namespace App\Http\Middleware;

use App\Domain\Personalization\Services\SegmentEvaluator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: Apply personalization rules.
 */
class ApplyPersonalization
{
    public function __construct(protected SegmentEvaluator $segmentEvaluator) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('personalization')) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        $visitorId = $request->cookie(config('personalization.visitor.cookie_name'));
        if (! $visitorId) {
            $visitorId = \Illuminate\Support\Str::uuid()->toString();
            cookie()->queue(
                config('personalization.visitor.cookie_name'),
                $visitorId,
                config('personalization.visitor.cookie_minutes')
            );
        }

        // Evaluate all segments for this visitor (cached in session)
        $matchedSegments = $this->segmentEvaluator->evaluateAll(tenant('id'), $request);

        // Cache matched segments in session for use in controllers/views
        session(['personalization.matched_segments' => $matchedSegments->pluck('id')->all()]);
        session(['personalization.visitor_id' => $visitorId]);

        return $next($request);
    }
}
