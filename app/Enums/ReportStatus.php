<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Published = 'published';
}
