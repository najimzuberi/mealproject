<?php

namespace App\Modules\Evaluation\Models;

use App\Modules\Indicator\Models\Indicator;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\Result;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationFinding extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid', 'organization_id', 'evaluation_id', 'reference', 'title', 'description',
        'supporting_evidence', 'project_id', 'result_id', 'indicator_id', 'conclusion', 'significance',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(EvaluationRecommendation::class);
    }
}
