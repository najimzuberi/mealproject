<?php

namespace App\Modules\Knowledge\Models;

use App\Enums\KnowledgeDocumentStatus;
use App\Models\User;
use App\Modules\Organization\Models\Department;
use App\Modules\Project\Models\Programme;
use App\Modules\Project\Models\Project;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\KnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeDocument extends Model
{
    /** @use HasFactory<KnowledgeDocumentFactory> */
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'code', 'title', 'category', 'description', 'document_type',
        'tags', 'keywords', 'project_id', 'programme_id', 'department_id', 'author_id',
        'published_on', 'version', 'access_level', 'status', 'disk', 'path', 'original_name',
        'mime_type', 'size', 'parent_document_id', 'ai_summary', 'ai_assisted',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'status' => KnowledgeDocumentStatus::class,
            'tags' => 'array',
            'keywords' => 'array',
            'published_on' => 'date',
            'version' => 'integer',
            'size' => 'integer',
            'ai_assisted' => 'boolean',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_document_id');
    }

    protected static function newFactory(): KnowledgeDocumentFactory
    {
        return KnowledgeDocumentFactory::new();
    }
}
