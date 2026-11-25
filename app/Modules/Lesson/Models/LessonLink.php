<?php

namespace App\Modules\Lesson\Models;

use App\Shared\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LessonLink extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'lesson_id', 'linkable_type', 'linkable_id',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
