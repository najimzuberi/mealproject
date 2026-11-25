<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';
}
