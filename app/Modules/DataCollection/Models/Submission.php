<?php

namespace App\Modules\DataCollection\Models;

use App\Enums\SubmissionStatus;
use App\Models\User;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\MonitoringPlan\Models\MonitoringPlanItem;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'reporting_assignment_id',
        'monitoring_plan_id',
        'monitoring_plan_item_id',
        'indicator_id',
        'reporting_period_id',
        'submitted_by',
        'status',
        'numerator',
        'denominator',
        'value',
        'disaggregations',
        'notes',
        'submitted_at',
        'verified_at',
        'verified_by',
        'validated_at',
        'validated_by',
        'approved_at',
        'approved_by',
        'published_at',
        'locked_at',
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
            'status' => SubmissionStatus::class,
            'numerator' => 'decimal:4',
            'denominator' => 'decimal:4',
            'value' => 'decimal:4',
            'disaggregations' => 'array',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'validated_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ReportingAssignment::class, 'reporting_assignment_id');
    }

    public function monitoringPlan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class);
    }

    public function monitoringPlanItem(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlanItem::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class);
    }

    protected static function newFactory(): SubmissionFactory
    {
        return SubmissionFactory::new();
    }
}
