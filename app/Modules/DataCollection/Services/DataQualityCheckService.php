<?php

namespace App\Modules\DataCollection\Services;

use App\Modules\DataCollection\Models\Submission;

class DataQualityCheckService
{
    /**
     * Flag potential quality issues for human review (does not reject).
     *
     * @return list<string>
     */
    public function evaluate(Submission $submission): array
    {
        $flags = [];

        if ($submission->value === null && $submission->numerator === null) {
            $flags[] = 'missing_value';
        }

        if ($submission->numerator !== null && $submission->denominator !== null && (float) $submission->denominator == 0.0) {
            $flags[] = 'zero_denominator';
        }

        if ($submission->numerator !== null && $submission->denominator !== null && (float) $submission->denominator != 0.0) {
            $expected = round(((float) $submission->numerator / (float) $submission->denominator) * 100, 2);
            if ($submission->value !== null && abs((float) $submission->value - $expected) > 1) {
                $flags[] = 'formula_inconsistency';
            }
        }

        $duplicate = Submission::query()
            ->where('organization_id', $submission->organization_id)
            ->where('monitoring_plan_item_id', $submission->monitoring_plan_item_id)
            ->where('reporting_period_id', $submission->reporting_period_id)
            ->where('id', '!=', $submission->id)
            ->whereNotNull('submitted_at')
            ->exists();

        if ($duplicate) {
            $flags[] = 'potential_duplicate';
        }

        if ($submission->value !== null && abs((float) $submission->value) > 1000000) {
            $flags[] = 'potential_outlier';
        }

        if ($submission->assignment?->due_on && now()->greaterThan($submission->assignment->due_on->endOfDay())) {
            $flags[] = 'late_submission';
        }

        return $flags;
    }
}
