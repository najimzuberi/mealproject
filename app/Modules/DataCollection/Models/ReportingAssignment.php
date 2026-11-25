<?php

namespace App\Modules\DataCollection\Models;

use App\Models\User;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\ReportingAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportingAssignment extends Model
{
    /** @use HasFactory<ReportingAssignmentFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'monitoring_plan_id',
        'monitoring_plan_item_id',
        'reporting_period_id',
        'assigned_to',
        'supervisor_id',
        'due_on',
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
            'due_on' => 'date',
        ];
    }

    public function monitoringPlan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class);
    }

    public function monitoringPlanItem(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlanItem::class);
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    protected static function newFactory(): ReportingAssignmentFactory
    {
        return ReportingAssignmentFactory::new();
    }
}
