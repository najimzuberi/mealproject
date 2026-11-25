<?php

namespace App\Modules\Dqa\Models;

use App\Enums\DqaAssessmentStatus;
use App\Models\User;
use App\Modules\DataCollection\Models\Submission;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\DqaAssessmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DqaAssessment extends Model
{
    /** @use HasFactory<DqaAssessmentFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'submission_id',
        'assessed_by',
        'status',
        'validity_score',
        'reliability_score',
        'completeness_score',
        'timeliness_score',
        'precision_score',
        'integrity_score',
        'overall_score',
        'summary',
        'completed_at',
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
            'status' => DqaAssessmentStatus::class,
            'overall_score' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(DqaIssue::class);
    }

    protected static function newFactory(): DqaAssessmentFactory
    {
        return DqaAssessmentFactory::new();
    }
}
