<?php

namespace App\Enums;

enum FeedbackCaseStatus: string
{
    case Received = 'received';
    case Assigned = 'assigned';
    case UnderInvestigation = 'under_investigation';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Referred = 'referred';
}
