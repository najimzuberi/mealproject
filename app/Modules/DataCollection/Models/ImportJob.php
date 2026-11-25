<?php

namespace App\Modules\DataCollection\Models;

use App\Models\User;
use App\Modules\MonitoringPlan\Models\MonitoringPlan;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportJob extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'monitoring_plan_id',
        'uploaded_by',
        'original_name',
        'disk',
        'path',
        'status',
        'total_rows',
        'success_rows',
        'failed_rows',
        'errors',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'total_rows' => 'integer',
            'success_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    public function monitoringPlan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
