<?php

namespace App\Modules\Dqa\Models;

use App\Models\User;
use App\Modules\DataCollection\Models\Submission;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\DqaIssueFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DqaIssue extends Model
{
    /** @use HasFactory<DqaIssueFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'dqa_assessment_id',
        'submission_id',
        'assigned_to',
        'dimension',
        'severity',
        'title',
        'description',
        'status',
        'resolution_notes',
        'resolved_at',
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
            'resolved_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(DqaAssessment::class, 'dqa_assessment_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected static function newFactory(): DqaIssueFactory
    {
        return DqaIssueFactory::new();
    }
}
