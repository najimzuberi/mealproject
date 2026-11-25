<?php

namespace App\Modules\StrategicPlanning\Models;

use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicGoal extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'strategic_plan_id',
        'strategic_theme_id',
        'code',
        'title',
        'category',
        'priority',
        'owner_user_id',
        'description',
        'expected_result',
        'start_date',
        'end_date',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'priority' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function strategicPlan(): BelongsTo
    {
        return $this->belongsTo(StrategicPlan::class);
    }

    public function strategicTheme(): BelongsTo
    {
        return $this->belongsTo(StrategicTheme::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(StrategicObjective::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'strategic_goal_department', 'goal_id', 'department_id');
    }
}
