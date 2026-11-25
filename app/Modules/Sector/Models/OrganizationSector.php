<?php

namespace App\Modules\Sector\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSector extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'sector_id',
        'is_primary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
