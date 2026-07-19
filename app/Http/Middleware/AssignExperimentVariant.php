<?php

namespace App\Http\Middleware;

use App\Domain\Experiment\Services\ExperimentEngine;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * V4: A/B Testing — assign visitor to experiment variant.
 */
class AssignExperimentVariant
{
    public function __construct(protected ExperimentEngine $engine) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant_has_feature('ab_testing')) {
            return $next($request);
        }

        if (! tenancy()->initialized) {
            return $next($request);
        }

        // Set visitor_id cookie if not present
        $visitorId = $request->cookie(config('experiments.visitor_cookie.name'));
        if (! $visitorId) {
            $visitorId = \Illuminate\Support\Str::uuid()->toString();
            cookie()->queue(
                config('experiments.visitor_cookie.name'),
                $visitorId,
                config('experiments.visitor_cookie.minutes')
            );
        }

        // Find any active experiment for the current route
        $experiment = $this->engine->findActiveForRoute($request->path());

        if ($experiment) {
            $variant = $this->engine->assignVisitor(
                $experiment,
                $visitorId,
                auth()->id(),
            );

            if ($variant) {
                $request->attributes->set('experiment_variant', $variant);
                $request->attributes->set('experiment_visitor_id', $visitorId);
            }
        }

        return $next($request);
    }
}
