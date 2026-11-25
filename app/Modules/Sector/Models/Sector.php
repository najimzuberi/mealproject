<?php

namespace App\Modules\Sector\Models;

use App\Modules\Organization\Models\Organization;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_system',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function organizationSectors(): HasMany
    {
        return $this->hasMany(OrganizationSector::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_sectors')
            ->withPivot(['is_primary', 'status'])
            ->withTimestamps();
    }

    protected static function newFactory(): SectorFactory
    {
        return SectorFactory::new();
    }
}
