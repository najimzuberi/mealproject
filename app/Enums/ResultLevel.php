<?php

namespace App\Enums;

enum ResultLevel: string
{
    case Impact = 'impact';
    case Outcome = 'outcome';
    case Output = 'output';
    case Process = 'process';
    case Activity = 'activity';
}
