<?php

namespace App\Enums;

enum ReportType: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
    case Project = 'project';
    case Programme = 'programme';
    case Indicator = 'indicator';
    case Monitoring = 'monitoring';
    case Compliance = 'compliance';
}
