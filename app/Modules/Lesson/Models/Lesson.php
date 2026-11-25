<?php

namespace App\Modules\Lesson\Models;

use App\Enums\LessonStatus;
use App\Models\User;
use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Feedback\Models\FeedbackCase;
use App\Modules\Learning\Models\LearningAction;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'reference', 'title', 'description', 'source',
        'project_id', 'programme_id', 'evaluation_id', 'feedback_case_id', 'context',
        'challenge', 'what_worked', 'what_did_not_work', 'recommendation',
        'responsible_person_id', 'action_required', 'status', 'created_by',
        'approved_at', 'approved_by', 'applied_at',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'status' => LessonStatus::class,
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function feedbackCase(): BelongsTo
    {
        return $this->belongsTo(FeedbackCase::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(LessonLink::class);
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(LearningAction::class, 'actionable');
    }

    protected static function newFactory(): LessonFactory
    {
        return LessonFactory::new();
    }
}
