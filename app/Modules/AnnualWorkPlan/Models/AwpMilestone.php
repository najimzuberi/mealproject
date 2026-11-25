<?php

namespace App\Modules\AnnualWorkPlan\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwpMilestone extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'awp_activity_id',
        'quarter',
        'title',
        'due_on',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
        ];
    }

    public function awpActivity(): BelongsTo
    {
        return $this->belongsTo(AwpActivity::class);
    }
}
