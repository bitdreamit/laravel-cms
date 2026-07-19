<?php

namespace App\Http\Controllers\Api;

use App\Domain\Experiment\Services\ExperimentEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExperimentApiController extends Controller
{
    public function __construct(protected ExperimentEngine $engine) {}

    public function convert(Request $request, string $experimentId)
    {
        $visitorId = $request->cookie(config('experiments.visitor_cookie.name'));
        if (! $visitorId) {
            return response()->json(['error' => 'No visitor ID found.'], 400);
        }

        $converted = $this->engine->trackConversion(
            $experimentId,
            $visitorId,
            $request->input('value'),
        );

        return response()->json(['converted' => $converted]);
    }
}
