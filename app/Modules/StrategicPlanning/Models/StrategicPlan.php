<?php

namespace App\Modules\StrategicPlanning\Models;

use App\Enums\StrategicPlanStatus;
use App\Models\User;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\StrategicPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicPlan extends Model
{
    /** @use HasFactory<StrategicPlanFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'code',
        'title',
        'description',
        'vision',
        'mission',
        'strategic_context',
        'core_values',
        'start_date',
        'end_date',
        'status',
        'version',
        'parent_plan_id',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => StrategicPlanStatus::class,
            'version' => 'integer',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'core_values' => 'array',
        ];
    }

    public function parentPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_plan_id');
    }

    public function childPlans(): HasMany
    {
        return $this->hasMany(self::class, 'parent_plan_id');
    }

    public function themes(): HasMany
    {
        return $this->hasMany(StrategicTheme::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(StrategicGoal::class);
    }

    public function priorities(): HasMany
    {
        return $this->hasMany(StrategicPlanPriority::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
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

    public function annualWorkPlans(): HasMany
    {
        return $this->hasMany(AnnualWorkPlan::class);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->start_date === null || $this->end_date === null) {
            return null;
        }

        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    protected static function newFactory(): StrategicPlanFactory
    {
        return StrategicPlanFactory::new();
    }
}
