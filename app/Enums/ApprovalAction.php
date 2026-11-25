<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Returned = 'returned';
    case Verified = 'verified';
    case Validated = 'validated';
    case Approved = 'approved';
    case Published = 'published';
    case Locked = 'locked';
    case Activated = 'activated';
    case Archived = 'archived';
    case Commented = 'commented';
}
