<?php

namespace App\Enums;

enum ConfidentialityLevel: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Restricted = 'restricted';
    case Safeguarding = 'safeguarding';
}
