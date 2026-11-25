<?php

namespace App\Modules\Indicator\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorBaseline extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'indicator_id',
        'value',
        'baseline_date',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'baseline_date' => 'date',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
