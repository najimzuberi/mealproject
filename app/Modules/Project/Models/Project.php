<?php

namespace App\Modules\Project\Models;

use App\Enums\ProjectStatus;
use App\Models\User;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Documents\Models\Document;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Organization\Models\Department;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\Sector\Models\Sector;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'programme_id',
        'code',
        'name',
        'description',
        'background',
        'problem_statement',
        'target_groups',
        'strategic_plan_id',
        'strategic_objective_id',
        'annual_work_plan_id',
        'sector_id',
        'department_id',
        'project_manager_id',
        'funding_source',
        'donor_name',
        'donor_information',
        'budget',
        'currency',
        'starts_on',
        'ends_on',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'created_by',
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
            'budget' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => ProjectStatus::class,
            'target_groups' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class);
    }

    public function annualWorkPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualWorkPlan::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role_label');
    }

    public function geographicUnits(): BelongsToMany
    {
        return $this->belongsToMany(GeographicUnit::class, 'project_geographic_unit');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function assumptions(): HasMany
    {
        return $this->hasMany(ProjectAssumption::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRisk::class);
    }

    public function resultsFrameworks(): HasMany
    {
        return $this->hasMany(ResultsFramework::class);
    }

    public function monitoringPlans(): HasMany
    {
        return $this->hasMany(MonitoringPlan::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function stakeholders(): MorphMany
    {
        return $this->morphMany(Stakeholder::class, 'stakeholderable');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
