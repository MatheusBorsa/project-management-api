<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'free';
    case PREMIUM = 'premium';
}