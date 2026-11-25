<?php

namespace App\Modules\Reports\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\User;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'reference_number',
        'title',
        'report_type',
        'status',
        'programme_id',
        'project_id',
        'reporting_period_id',
        'starts_on',
        'ends_on',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'executive_summary',
        'performance_summary',
        'challenges',
        'risks',
        'recommendations',
        'ai_assisted',
        'snapshot',
        'submitted_at',
        'approved_at',
        'published_at',
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
            'status' => ReportStatus::class,
            'report_type' => ReportType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'ai_assisted' => 'boolean',
            'snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function indicatorResults(): HasMany
    {
        return $this->hasMany(ReportIndicatorResult::class);
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }
}
