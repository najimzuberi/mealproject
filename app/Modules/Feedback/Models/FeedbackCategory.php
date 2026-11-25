<?php

namespace App\Modules\Feedback\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackCategory extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'name', 'code', 'is_sensitive', 'is_active',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(FeedbackCase::class);
    }
}
