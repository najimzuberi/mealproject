<?php

namespace App\Modules\Geography\Models;

use App\Modules\Organization\Models\Organization;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\GeographicTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeographicType extends Model
{
    /** @use HasFactory<GeographicTypeFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'name',
        'code',
        'default_level',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_level' => 'integer',
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

    public function units(): HasMany
    {
        return $this->hasMany(GeographicUnit::class);
    }

    protected static function newFactory(): GeographicTypeFactory
    {
        return GeographicTypeFactory::new();
    }
}
