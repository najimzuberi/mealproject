<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Government = 'government';
    case Ngo = 'ngo';
    case Private = 'private';
    case International = 'international';
    case Other = 'other';
}
