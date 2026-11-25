<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Models\User;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Team;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicOutcome;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AwpActivity extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'annual_work_plan_id',
        'awp_priority_id',
        'code',
        'department_id',
        'responsible_user_id',
        'team_id',
        'geographic_unit_id',
        'strategic_outcome_id',
        'title',
        'description',
        'deliverable',
        'expected_output',
        'budget_estimate',
        'funding_source',
        'starts_on',
        'ends_on',
        'quarter',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_estimate' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function annualWorkPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualWorkPlan::class);
    }

    public function awpPriority(): BelongsTo
    {
        return $this->belongsTo(AwpPriority::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function geographicUnit(): BelongsTo
    {
        return $this->belongsTo(GeographicUnit::class);
    }

    public function strategicOutcome(): BelongsTo
    {
        return $this->belongsTo(StrategicOutcome::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(AwpMilestone::class);
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(AwpBudgetLine::class);
    }

    public function objectives(): BelongsToMany
    {
        return $this->belongsToMany(
            StrategicObjective::class,
            'activity_objectives',
            'awp_activity_id',
            'strategic_objective_id',
        );
    }
}
