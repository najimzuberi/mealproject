<?php

namespace App\Modules\Feedback\Models;

use App\Enums\ConfidentialityLevel;
use App\Enums\FeedbackCaseStatus;
use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Geography\Models\GeographicUnit;
use App\Modules\Organization\Models\Department;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\FeedbackCaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackCase extends Model
{
    /** @use HasFactory<FeedbackCaseFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'reference_number', 'submitted_at', 'feedback_type',
        'feedback_category_id', 'description', 'submission_channel', 'project_id',
        'geographic_unit_id', 'is_anonymous', 'complainant_name', 'complainant_contact',
        'consent_given', 'confidentiality_level', 'priority', 'status', 'assigned_to',
        'responsible_department_id', 'supervisor_id', 'resolution_deadline', 'escalation_level',
        'escalation_reason', 'investigation_notes', 'resolution_description', 'action_taken',
        'resolved_at', 'closed_at', 'closed_by', 'feedback_loop_status', 'created_by',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'status' => FeedbackCaseStatus::class,
            'confidentiality_level' => ConfidentialityLevel::class,
            'submitted_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'consent_given' => 'boolean',
            'resolution_deadline' => 'date',
            'escalation_level' => 'integer',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function isRestricted(): bool
    {
        $level = $this->confidentiality_level instanceof ConfidentialityLevel
            ? $this->confidentiality_level
            : ConfidentialityLevel::tryFrom((string) $this->confidentiality_level);

        return in_array($level, [ConfidentialityLevel::Restricted, ConfidentialityLevel::Safeguarding], true)
            || (bool) $this->category?->is_sensitive;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeedbackCategory::class, 'feedback_category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function geographicUnit(): BelongsTo
    {
        return $this->belongsTo(GeographicUnit::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'responsible_department_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    protected static function newFactory(): FeedbackCaseFactory
    {
        return FeedbackCaseFactory::new();
    }
}
