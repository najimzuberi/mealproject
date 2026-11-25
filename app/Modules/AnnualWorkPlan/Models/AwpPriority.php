<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AwpPriority extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'annual_work_plan_id',
        'strategic_objective_id',
        'code',
        'department_id',
        'title',
        'description',
        'priority_level',
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
            'priority_level' => 'integer',
        ];
    }

    public function annualWorkPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualWorkPlan::class);
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AwpActivity::class);
    }
}
