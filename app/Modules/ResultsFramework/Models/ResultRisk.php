<?php

namespace App\Modules\ResultsFramework\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultRisk extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'result_id',
        'description',
        'likelihood',
        'impact',
        'mitigation',
        'status',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
