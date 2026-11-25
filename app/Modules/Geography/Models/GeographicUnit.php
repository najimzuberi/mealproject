<?php

namespace App\Modules\Geography\Models;

use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\GeographicUnitFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeographicUnit extends Model
{
    /** @use HasFactory<GeographicUnitFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'parent_id',
        'geographic_type_id',
        'name',
        'code',
        'level',
        'type',
        'description',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function geographicType(): BelongsTo
    {
        return $this->belongsTo(GeographicType::class);
    }

    protected static function newFactory(): GeographicUnitFactory
    {
        return GeographicUnitFactory::new();
    }
}
