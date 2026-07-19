<?php

namespace App\Domain\Experiment\Services;

use App\Models\Tenant\Experiment;
use App\Models\Tenant\ExperimentAssignment;
use App\Models\Tenant\ExperimentVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExperimentEngine
{
    /**
     * Find an active experiment for a given entry.
     */
    public function findActiveForEntry(string $entryId, string $collectionHandle = null): ?Experiment
    {
        $query = Experiment::where('tenant_id', tenant('id'))
            ->where('status', 'running')
            ->where(function ($q) use ($entryId, $collectionHandle) {
                $q->where('entry_id', $entryId);
                if ($collectionHandle) {
                    $q->orWhere('collection_handle', $collectionHandle);
                }
            })
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>', now());
            });

        return $query->first();
    }

    /**
     * Find an active experiment for a route (for template/CTA variants).
     */
    public function findActiveForRoute(string $path): ?Experiment
    {
        return Experiment::where('tenant_id', tenant('id'))
            ->where('status', 'running')
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>', now());
            })
            ->first();
    }

    /**
     * Assign a visitor to a variant (sticky — same visitor gets same variant).
     */
    public function assignVisitor(Experiment $experiment, string $visitorId, ?string $userId = null): ?ExperimentVariant
    {
        // Check if visitor is already assigned
        $existing = ExperimentAssignment::where('experiment_id', $experiment->id)
            ->where('visitor_id', $visitorId)
            ->first();

        if ($existing) {
            return $existing->variant;
        }

        // Check traffic allocation
        if (! $this->isInExperiment($experiment->traffic_allocation)) {
            return null;
        }

        // Weighted random selection
        $variant = $this->selectWeightedRandom($experiment->variants);

        if (! $variant) return null;

        ExperimentAssignment::create([
            'id' => Str::uuid(),
            'tenant_id' => $experiment->tenant_id,
            'experiment_id' => $experiment->id,
            'variant_id' => $variant->id,
            'visitor_id' => $visitorId,
            'user_id' => $userId,
            'assigned_at' => now(),
        ]);

        return $variant;
    }

    /**
     * Track a conversion for an experiment assignment.
     */
    public function trackConversion(string $experimentId, string $visitorId, ?float $value = null): bool
    {
        $assignment = ExperimentAssignment::where('experiment_id', $experimentId)
            ->where('visitor_id', $visitorId)
            ->first();

        if (! $assignment || $assignment->isConverted()) {
            return false; // Already converted or no assignment
        }

        $assignment->update([
            'converted_at' => now(),
            'conversion_value' => $value,
        ]);

        return true;
    }

    /**
     * Determine if a visitor should be in the experiment (traffic allocation check).
     */
    protected function isInExperiment(int $trafficAllocation): bool
    {
        if ($trafficAllocation >= 100) return true;
        if ($trafficAllocation <= 0) return false;
        return random_int(1, 100) <= $trafficAllocation;
    }

    /**
     * Select a variant using weighted random selection.
     */
    protected function selectWeightedRandom($variants): ?ExperimentVariant
    {
        if ($variants->isEmpty()) return null;

        $totalWeight = $variants->sum('weight');
        if ($totalWeight <= 0) {
            return $variants->first();
        }

        $random = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->weight;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return $variants->last();
    }

    /**
     * Get statistical significance data for an experiment.
     */
    public function calculateStatistics(Experiment $experiment): array
    {
        $stats = [];
        $control = $experiment->controlVariant();

        foreach ($experiment->variants as $variant) {
            $visitors = $variant->assignments()->count();
            $conversions = $variant->assignments()->whereNotNull('converted_at')->count();
            $conversionRate = $visitors > 0 ? ($conversions / $visitors) * 100 : 0;

            $lift = 0;
            $confidence = 0;

            if ($control && $control->id !== $variant->id) {
                $controlVisitors = $control->assignments()->count();
                $controlConversions = $control->assignments()->whereNotNull('converted_at')->count();
                $controlRate = $controlVisitors > 0 ? $controlConversions / $controlVisitors : 0;
                $variantRate = $visitors > 0 ? $conversions / $visitors : 0;

                if ($controlRate > 0) {
                    $lift = (($variantRate - $controlRate) / $controlRate) * 100;
                }

                // Two-proportion z-test for confidence
                if ($visitors >= $experiment->min_sample_size && $controlVisitors >= $experiment->min_sample_size) {
                    $confidence = $this->zTest($controlConversions, $controlVisitors, $conversions, $visitors);
                }
            }

            $stats[] = [
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'is_control' => $variant->is_control,
                'visitors' => $visitors,
                'conversions' => $conversions,
                'conversion_rate' => round($conversionRate, 4),
                'lift_vs_control' => round($lift, 2),
                'confidence' => round($confidence, 4),
                'is_significant' => $confidence >= $experiment->confidence_threshold,
            ];
        }

        return $stats;
    }

    /**
     * Two-proportion z-test.
     */
    protected function zTest(int $c1, int $n1, int $c2, int $n2): float
    {
        if ($n1 === 0 || $n2 === 0) return 0.0;

        $p1 = $c1 / $n1;
        $p2 = $c2 / $n2;
        $pPooled = ($c1 + $c2) / ($n1 + $n2);

        if ($pPooled == 0 || $pPooled == 1) return 0.0;

        $se = sqrt($pPooled * (1 - $pPooled) * (1/$n1 + 1/$n2));
        if ($se == 0) return 0.0;

        $z = ($p2 - $p1) / $se;

        // Convert z-score to p-value via standard normal CDF approximation
        $pValue = 2 * (1 - $this->normalCdf(abs($z)));
        return 1 - $pValue; // confidence
    }

    /**
     * Standard normal CDF approximation (Abramowitz & Stegun).
     */
    protected function normalCdf(float $x): float
    {
        $t = 1 / (1 + 0.2316419 * $x);
        $d = 0.3989423 * exp(-$x * $x / 2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        return 1 - $p;
    }
}
