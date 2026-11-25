<?php

namespace App\Modules\Project\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRisk extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'project_id',
        'description',
        'likelihood',
        'impact',
        'mitigation',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
