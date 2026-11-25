<?php

namespace App\Modules\Knowledge\Resources;

use App\Modules\Knowledge\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeDocument */
class KnowledgeDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description,
            'document_type' => $this->document_type,
            'tags' => $this->tags,
            'keywords' => $this->keywords,
            'project_id' => $this->project_id,
            'programme_id' => $this->programme_id,
            'department_id' => $this->department_id,
            'author_id' => $this->author_id,
            'published_on' => $this->published_on?->toDateString(),
            'version' => $this->version,
            'access_level' => $this->access_level,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'has_file' => filled($this->path),
            'parent_document_id' => $this->parent_document_id,
            'ai_summary' => $this->ai_summary,
            'ai_assisted' => $this->ai_assisted,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
