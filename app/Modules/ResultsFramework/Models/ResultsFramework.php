<?php

namespace App\Modules\ResultsFramework\Models;

use App\Enums\WorkflowStatus;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\ResultsFrameworkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultsFramework extends Model
{
    /** @use HasFactory<ResultsFrameworkFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'project_id',
        'title',
        'theory_of_change',
        'status',
        'version',
        'parent_framework_id',
        'submitted_at',
        'approved_at',
        'approved_by',
        'created_by',
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
            'status' => WorkflowStatus::class,
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parentFramework(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_framework_id');
    }

    public function childFrameworks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_framework_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    protected static function newFactory(): ResultsFrameworkFactory
    {
        return ResultsFrameworkFactory::new();
    }
}
