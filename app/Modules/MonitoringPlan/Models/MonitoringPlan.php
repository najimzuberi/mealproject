<?php

namespace App\Modules\MonitoringPlan\Models;

use App\Enums\MonitoringPlanStatus;
use App\Models\User;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\MonitoringPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitoringPlan extends Model
{
    /** @use HasFactory<MonitoringPlanFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'code',
        'project_id',
        'title',
        'description',
        'status',
        'version',
        'parent_monitoring_plan_id',
        'intended_use',
        'assumptions',
        'limitations',
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
            'status' => MonitoringPlanStatus::class,
            'version' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_monitoring_plan_id');
    }

    public function childPlans(): HasMany
    {
        return $this->hasMany(self::class, 'parent_monitoring_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonitoringPlanItem::class)->orderBy('sort_order');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): MonitoringPlanFactory
    {
        return MonitoringPlanFactory::new();
    }
}
