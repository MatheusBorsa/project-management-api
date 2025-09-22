<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case UNDER_REVIEW = 'under_review';
    case COMPLETED = 'completed';
}
