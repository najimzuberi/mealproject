<?php

namespace App\Modules\Organization\Models;

use App\Models\User;
use App\Modules\AnnualWorkPlan\Models\AnnualWorkPlan;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Indicator\Models\Indicator;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Modules\ReportingCalendar\Models\ReportingCalendar;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\Sector\Models\OrganizationSector;
use App\Modules\Sector\Models\Sector;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'code',
        'registration_number',
        'type',
        'description',
        'email',
        'phone',
        'website',
        'logo_path',
        'country',
        'headquarters_region',
        'physical_address',
        'postal_address',
        'address',
        'timezone',
        'default_language',
        'default_currency',
        'status',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(OrganizationSetting::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot(['status', 'is_default', 'joined_at'])
            ->withTimestamps();
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function facilityTypes(): HasMany
    {
        return $this->hasMany(FacilityType::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function geographicUnits(): HasMany
    {
        return $this->hasMany(GeographicUnit::class);
    }

    public function reportingCalendars(): HasMany
    {
        return $this->hasMany(ReportingCalendar::class);
    }

    public function organizationSectors(): HasMany
    {
        return $this->hasMany(OrganizationSector::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'organization_sectors')
            ->withPivot(['is_primary', 'status'])
            ->withTimestamps();
    }

    public function strategicPlans(): HasMany
    {
        return $this->hasMany(StrategicPlan::class);
    }

    public function annualWorkPlans(): HasMany
    {
        return $this->hasMany(AnnualWorkPlan::class);
    }

    public function programmes(): HasMany
    {
        return $this->hasMany(Programme::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function resultsFrameworks(): HasMany
    {
        return $this->hasMany(ResultsFramework::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function monitoringPlans(): HasMany
    {
        return $this->hasMany(MonitoringPlan::class);
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }
}
