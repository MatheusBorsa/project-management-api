<?php

namespace App\Enums;

enum ClientUserRole: string
{
    case OWNER = 'owner';
    case PARTICIPANT = 'participant';
    case VIEWER = 'viewer';
    case CLIENT = 'client';
}