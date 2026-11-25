<?php

namespace App\Shared\Models;

use App\Enums\ApprovalAction;
use App\Models\User;
use App\Modules\Organization\Models\Organization;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalHistory extends Model
{
    use BelongsToOrganization;

    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'approvable_type',
        'approvable_id',
        'action',
        'from_status',
        'to_status',
        'user_id',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ApprovalAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
