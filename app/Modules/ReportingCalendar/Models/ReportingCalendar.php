<?php

namespace App\Modules\ReportingCalendar\Models;

use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\ReportingCalendarFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportingCalendar extends Model
{
    /** @use HasFactory<ReportingCalendarFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'name',
        'frequency',
        'description',
        'allow_overlapping_periods',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'allow_overlapping_periods' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(ReportingPeriod::class);
    }

    protected static function newFactory(): ReportingCalendarFactory
    {
        return ReportingCalendarFactory::new();
    }
}
