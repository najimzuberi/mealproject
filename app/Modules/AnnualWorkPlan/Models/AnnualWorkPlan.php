<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Enums\StrategicPlanStatus;
use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\AnnualWorkPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualWorkPlan extends Model
{
    /** @use HasFactory<AnnualWorkPlanFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'strategic_plan_id',
        'code',
        'title',
        'planning_year',
        'start_date',
        'end_date',
        'currency',
        'total_budget',
        'description',
        'status',
        'version',
        'parent_awp_id',
        'approved_at',
        'submitted_at',
        'approved_by',
        'created_by',
        'owner_user_id',
        'department_id',
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
            'planning_year' => 'integer',
            'status' => StrategicPlanStatus::class,
            'version' => 'integer',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_budget' => 'decimal:2',
        ];
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function parentAwp(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_awp_id');
    }

    public function childAwps(): HasMany
    {
        return $this->hasMany(self::class, 'parent_awp_id');
    }

    public function priorities(): HasMany
    {
        return $this->hasMany(AwpPriority::class);
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(AwpBudgetLine::class);
    }

    public function planMilestones(): HasMany
    {
        return $this->hasMany(AwpPlanMilestone::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): AnnualWorkPlanFactory
    {
        return AnnualWorkPlanFactory::new();
    }
}
