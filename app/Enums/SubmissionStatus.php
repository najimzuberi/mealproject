<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Verified = 'verified';
    case Validated = 'validated';
    case Approved = 'approved';
    case Published = 'published';
    case Locked = 'locked';
}
