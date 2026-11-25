<?php

namespace App\Modules\AccessControl\Models;

use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Org-scoped roles use Spatie teams (organization_id).
 * User-organization role assignments are stored in model_has_roles with organization_id — not a separate user_organization_roles table.
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'organization_id',
        'display_name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
