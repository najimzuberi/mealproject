<?php

namespace App\Modules\Learning\Models;

use App\Enums\LearningActionStatus;
use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningAction extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'actionable_type', 'actionable_id', 'description',
        'responsible_person_id', 'department_id', 'due_on', 'required_evidence',
        'status', 'completed_on', 'progress_notes', 'created_by',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'status' => LearningActionStatus::class,
            'due_on' => 'date',
            'completed_on' => 'date',
        ];
    }

    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
