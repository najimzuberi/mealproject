<?php

namespace App\Modules\ResultsFramework\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultAssumption extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'result_id',
        'description',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
