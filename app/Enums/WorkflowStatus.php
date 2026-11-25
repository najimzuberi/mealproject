<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Active = 'active';
    case Superseded = 'superseded';
    case Archived = 'archived';
}
