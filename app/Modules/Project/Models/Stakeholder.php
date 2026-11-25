<?php

namespace App\Modules\Project\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stakeholder extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'stakeholderable_type',
        'stakeholderable_id',
        'name',
        'type',
        'role',
        'contact_email',
        'contact_phone',
        'notes',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function stakeholderable(): MorphTo
    {
        return $this->morphTo();
    }
}
