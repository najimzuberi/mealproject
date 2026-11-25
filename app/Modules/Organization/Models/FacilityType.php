<?php

namespace App\Modules\Organization\Models;

use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\FacilityTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityType extends Model
{
    /** @use HasFactory<FacilityTypeFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'name',
        'code',
        'description',
        'status',
    ];

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

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    protected static function newFactory(): FacilityTypeFactory
    {
        return FacilityTypeFactory::new();
    }
}
