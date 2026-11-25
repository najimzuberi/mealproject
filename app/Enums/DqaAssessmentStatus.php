<?php

namespace App\Enums;

enum DqaAssessmentStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';
}
