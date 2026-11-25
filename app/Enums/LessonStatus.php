<?php

namespace App\Enums;

enum LessonStatus: string
{
    case Identified = 'identified';
    case Documented = 'documented';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case ActionAssigned = 'action_assigned';
    case Applied = 'applied';
    case Archived = 'archived';
}
