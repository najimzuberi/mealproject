<?php

namespace App\Enums;

enum LearningActionStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';
}
