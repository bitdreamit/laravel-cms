<?php

namespace App\Domain\Form\Actions;

use App\Models\Tenant\FormLeadScoringRule;
use App\Models\Tenant\FormSubmission;

class ScoreLead
{
    public function execute(FormSubmission $submission, FormLeadScoringRule $rules): void
    {
        $breakdown = [];
        $totalScore = 0;

        foreach ($rules->rules ?? [] as $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? '=';
            $expected = $rule['value'] ?? null;
            $points = $rule['points'] ?? 0;

            $actual = $submission->data[$field] ?? null;

            if ($this->matches($actual, $operator, $expected)) {
                $totalScore += $points;
                $breakdown[$field] = $points;
            }
        }

        $isQualified = $totalScore >= $rules->threshold_for_qualified;

        $submission->update([
            'lead_score' => $totalScore,
            'lead_score_breakdown' => $breakdown,
            'is_qualified' => $isQualified,
        ]);

        if ($isQualified) {
            event(new \App\Domain\Form\Events\LeadQualified($submission));
        }
    }

    protected function matches($actual, string $operator, $expected): bool
    {
        if ($actual === null) return false;

        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => is_numeric($actual) && $actual > $expected,
            '>=' => is_numeric($actual) && $actual >= $expected,
            '<' => is_numeric($actual) && $actual < $expected,
            '<=' => is_numeric($actual) && $actual <= $expected,
            'in' => in_array($actual, (array) $expected),
            'not_in' => ! in_array($actual, (array) $expected),
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            default => false,
        };
    }
}
