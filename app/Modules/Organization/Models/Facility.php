<?php

namespace App\Modules\Organization\Models;

use App\Modules\Geography\Models\GeographicUnit;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'office_id',
        'facility_type_id',
        'geographic_unit_id',
        'name',
        'code',
        'type',
        'description',
        'registration_number',
        'address',
        'latitude',
        'longitude',
        'contact_person',
        'contact_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function facilityType(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class);
    }

    public function geographicUnit(): BelongsTo
    {
        return $this->belongsTo(GeographicUnit::class);
    }

    protected static function newFactory(): FacilityFactory
    {
        return FacilityFactory::new();
    }
}
