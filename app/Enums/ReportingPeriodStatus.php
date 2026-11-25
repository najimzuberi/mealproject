<?php

namespace App\Enums;

enum ReportingPeriodStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
