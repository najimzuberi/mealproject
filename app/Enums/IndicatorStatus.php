<?php

namespace App\Enums;

enum IndicatorStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Superseded = 'superseded';
    case Retired = 'retired';
}
