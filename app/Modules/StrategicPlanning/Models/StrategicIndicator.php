<?php

namespace App\Modules\StrategicPlanning\Models;

use App\Modules\Indicator\Models\Indicator;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicIndicator extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'strategic_plan_id',
        'strategic_objective_id',
        'strategic_outcome_id',
        'department_id',
        'indicator_id',
        'code',
        'name',
        'definition',
        'measurement_unit',
        'measurement_direction',
        'baseline_value',
        'baseline_date',
        'target_value',
        'target_period',
        'data_source',
        'measurement_frequency',
        'status',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:4',
            'target_value' => 'decimal:4',
            'baseline_date' => 'date',
        ];
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class);
    }

    public function strategicOutcome(): BelongsTo
    {
        return $this->belongsTo(StrategicOutcome::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function libraryIndicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'indicator_id');
    }
}
