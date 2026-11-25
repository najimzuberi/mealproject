<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Team;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AwpPlanMilestone extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $table = 'awp_milestones_plan';

    protected $fillable = [
        'ulid',
        'organization_id',
        'annual_work_plan_id',
        'strategic_objective_id',
        'department_id',
        'team_id',
        'code',
        'title',
        'description',
        'quarter',
        'start_date',
        'end_date',
        'expected_result',
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
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function annualWorkPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualWorkPlan::class);
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
