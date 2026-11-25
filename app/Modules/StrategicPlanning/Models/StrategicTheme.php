<?php

namespace App\Modules\StrategicPlanning\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicTheme extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'strategic_plan_id',
        'name',
        'description',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(StrategicGoal::class);
    }
}
