<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';
}
