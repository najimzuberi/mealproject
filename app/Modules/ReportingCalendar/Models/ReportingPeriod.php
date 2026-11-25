<?php

namespace App\Modules\ReportingCalendar\Models;

use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\ReportingPeriodFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportingPeriod extends Model
{
    /** @use HasFactory<ReportingPeriodFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'reporting_calendar_id',
        'organization_id',
        'name',
        'code',
        'starts_on',
        'ends_on',
        'submission_deadline',
        'status',
        'opened_at',
        'closed_at',
        'closure_notes',
        'responsible_officer_id',
        'reminder_days_before',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'submission_deadline' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'reminder_days_before' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function reportingCalendar(): BelongsTo
    {
        return $this->belongsTo(ReportingCalendar::class);
    }

    protected static function newFactory(): ReportingPeriodFactory
    {
        return ReportingPeriodFactory::new();
    }
}
