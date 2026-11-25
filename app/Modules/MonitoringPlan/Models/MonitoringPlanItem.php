<?php

namespace App\Modules\MonitoringPlan\Models;

use App\Models\User;
use App\Modules\Indicator\Models\Indicator;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\MonitoringPlanItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitoringPlanItem extends Model
{
    /** @use HasFactory<MonitoringPlanItemFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'monitoring_plan_id',
        'indicator_id',
        'baseline_value',
        'target_value',
        'data_source',
        'collection_method',
        'collection_frequency',
        'collection_deadline',
        'responsible_collector_id',
        'supervisor_id',
        'me_validator_id',
        'means_of_verification',
        'validation_rules',
        'reporting_format',
        'intended_use',
        'assumptions',
        'limitations',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection_deadline' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function monitoringPlan(): BelongsTo
    {
        return $this->belongsTo(MonitoringPlan::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function responsibleCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_collector_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function meValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'me_validator_id');
    }

    protected static function newFactory(): MonitoringPlanItemFactory
    {
        return MonitoringPlanItemFactory::new();
    }
}
