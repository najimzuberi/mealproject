<?php

namespace App\Modules\StrategicPlanning\Models;

use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicObjective extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'strategic_goal_id',
        'code',
        'title',
        'priority',
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

    public function strategicGoal(): BelongsTo
    {
        return $this->belongsTo(StrategicGoal::class);
    }

    public function keyResultAreas(): HasMany
    {
        return $this->hasMany(KeyResultArea::class);
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(StrategicOutcome::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(StrategicIndicator::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'strategic_objective_department', 'objective_id', 'department_id');
    }

    public function responsibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'strategic_objective_user', 'objective_id', 'user_id');
    }
}
