<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AwpBudgetLine extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'annual_work_plan_id',
        'awp_activity_id',
        'department_id',
        'code',
        'category',
        'description',
        'amount',
        'currency',
        'funding_source',
        'financial_year',
        'quarter',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'financial_year' => 'integer',
        ];
    }

    public function annualWorkPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualWorkPlan::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AwpActivity::class, 'awp_activity_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
