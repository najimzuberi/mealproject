<?php

namespace App\Modules\Indicator\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorTarget extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'indicator_id',
        'value',
        'target_period',
        'starts_on',
        'ends_on',
        'is_cumulative',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_cumulative' => 'boolean',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
