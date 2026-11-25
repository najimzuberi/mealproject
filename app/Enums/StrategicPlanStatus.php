<?php

namespace App\Enums;

enum StrategicPlanStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';
}
