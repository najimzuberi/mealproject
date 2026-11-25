<?php

namespace App\Modules\ResultsFramework\Models;

use App\Enums\ResultLevel;
use App\Modules\Organization\Models\Department;
use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'organization_id',
        'results_framework_id',
        'parent_id',
        'level',
        'code',
        'title',
        'description',
        'sort_order',
        'department_id',
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
            'level' => ResultLevel::class,
            'sort_order' => 'integer',
        ];
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(ResultsFramework::class, 'results_framework_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assumptions(): HasMany
    {
        return $this->hasMany(ResultAssumption::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(ResultRisk::class);
    }
}
