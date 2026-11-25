<?php

namespace App\Modules\Reports\Models;

use App\Modules\Indicator\Models\Indicator;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingPeriod;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportIndicatorResult extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'report_id',
        'indicator_id',
        'project_id',
        'reporting_period_id',
        'indicator_code',
        'indicator_name',
        'baseline',
        'target',
        'actual',
        'achievement_percent',
        'variance',
        'performance_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'baseline' => 'decimal:4',
            'target' => 'decimal:4',
            'actual' => 'decimal:4',
            'achievement_percent' => 'decimal:2',
            'variance' => 'decimal:4',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }
}
