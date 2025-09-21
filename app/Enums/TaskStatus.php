<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case WAITING_APPROVAL = 'waiting_approval';
    case REJECTED = 'rejected';
    case APPROVED = 'approved'; 
}
