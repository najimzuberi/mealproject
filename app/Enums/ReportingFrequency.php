<?php

namespace App\Enums;

enum ReportingFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual = 'annual';
    case Custom = 'custom';
}
