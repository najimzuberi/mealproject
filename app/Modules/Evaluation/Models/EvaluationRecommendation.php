<?php

namespace App\Modules\Evaluation\Models;

use App\Models\User;
use App\Modules\Learning\Models\LearningAction;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationRecommendation extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'evaluation_id', 'evaluation_finding_id', 'title', 'description',
        'priority', 'supporting_evidence', 'responsible_department_id', 'management_response_status',
        'management_response', 'response_date', 'responsible_manager_id',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'response_date' => 'date',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(EvaluationFinding::class, 'evaluation_finding_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'responsible_department_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_manager_id');
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(LearningAction::class, 'actionable');
    }
}
