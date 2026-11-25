<?php

namespace App\Modules\Evaluation\Models;

use App\Enums\EvaluationStatus;
use App\Models\User;
use App\Modules\Learning\Models\LearningAction;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'code', 'title', 'evaluation_type', 'programme_id', 'project_id',
        'purpose', 'scope', 'starts_on', 'ends_on', 'evaluation_lead_id', 'evaluation_team',
        'methodology', 'data_collection_methods', 'target_population', 'sample_size',
        'geographic_coverage', 'tools', 'timeline_notes', 'status', 'created_by',
        'approved_at', 'approved_by', 'completed_at',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'evaluation_team' => 'array',
            'sample_size' => 'integer',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluation_lead_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(EvaluationFinding::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(EvaluationRecommendation::class);
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(LearningAction::class, 'actionable');
    }

    protected static function newFactory(): EvaluationFactory
    {
        return EvaluationFactory::new();
    }
}
