<?php

namespace App\Modules\Project\Models;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Department;
use App\Modules\Sector\Models\Sector;
use App\Modules\StrategicPlanning\Models\StrategicPlan;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Programme extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'code',
        'name',
        'description',
        'objectives',
        'strategic_alignment',
        'sector_id',
        'department_id',
        'programme_manager_id',
        'strategic_plan_id',
        'starts_on',
        'ends_on',
        'budget_envelope',
        'currency',
        'donor_name',
        'donor_information',
        'partners',
        'target_groups',
        'thematic_area',
        'status',
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
            'starts_on' => 'date',
            'ends_on' => 'date',
            'budget_envelope' => 'decimal:2',
            'partners' => 'array',
            'target_groups' => 'array',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programmeManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'programme_manager_id');
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'programme_user')
            ->withPivot('role_label');
    }

    public function geographicUnits(): BelongsToMany
    {
        return $this->belongsToMany(GeographicUnit::class, 'programme_geographic_unit');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function stakeholders(): MorphMany
    {
        return $this->morphMany(Stakeholder::class, 'stakeholderable');
    }
}
