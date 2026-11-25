<?php

namespace App\Modules\DataCollection\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionValue extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'submission_id',
        'dimension',
        'label',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
