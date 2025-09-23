<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';
}