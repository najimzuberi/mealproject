<?php

namespace App\Modules\Organization\Models;

use App\Models\User;
use App\Modules\Geography\Models\GeographicUnit;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'department_id',
        'parent_id',
        'geographic_unit_id',
        'manager_user_id',
        'name',
        'code',
        'type',
        'description',
        'address',
        'country',
        'region',
        'district',
        'contact_person',
        'email',
        'phone',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function geographicUnit(): BelongsTo
    {
        return $this->belongsTo(GeographicUnit::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    protected static function newFactory(): OfficeFactory
    {
        return OfficeFactory::new();
    }
}
