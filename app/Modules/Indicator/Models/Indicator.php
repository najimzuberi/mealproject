<?php

namespace App\Modules\Indicator\Models;

use App\Enums\IndicatorStatus;
use App\Enums\ResultLevel;
use App\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\ResultsFramework\Models\Result;
use App\Modules\ResultsFramework\Models\ResultsFramework;
use App\Modules\StrategicPlanning\Models\StrategicObjective;
use App\Shared\Traits\BelongsToOrganization;
use App\Shared\Traits\HasApprovalHistory;
use Database\Factories\IndicatorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Indicator extends Model
{
    /** @use HasFactory<IndicatorFactory> */
    use BelongsToOrganization, HasApprovalHistory, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'code',
        'name',
        'description',
        'definition',
        'measurement_unit',
        'numerator',
        'denominator',
        'formula',
        'aggregation_method',
        'direction_of_change',
        'result_level',
        'strategic_objective_id',
        'project_id',
        'results_framework_id',
        'result_id',
        'indicator_type',
        'status',
        'version',
        'parent_indicator_id',
        'source_url',
        'source_organization',
        'source_accessed_on',
        'adaptation_notes',
        'data_source',
        'collection_method',
        'collection_frequency',
        'responsible_collector_id',
        'supervisor_id',
        'me_validator_id',
        'means_of_verification',
        'validation_rules',
        'limitations',
        'assumptions',
        'quality_checks',
        'approved_at',
        'approved_by',
        'created_by',
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
            'status' => IndicatorStatus::class,
            'result_level' => ResultLevel::class,
            'version' => 'integer',
            'source_accessed_on' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function strategicObjective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function resultsFramework(): BelongsTo
    {
        return $this->belongsTo(ResultsFramework::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function parentIndicator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_indicator_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_indicator_id');
    }

    public function baselines(): HasMany
    {
        return $this->hasMany(IndicatorBaseline::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(IndicatorTarget::class);
    }

    public function disaggregations(): HasMany
    {
        return $this->hasMany(IndicatorDisaggregation::class);
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): IndicatorFactory
    {
        return IndicatorFactory::new();
    }
}
