<?php

namespace App\Modules\Evaluation\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'evaluation_id', 'question', 'criterion', 'sort_order',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
