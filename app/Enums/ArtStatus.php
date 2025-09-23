<?php

namespace App\Enums;

enum ArtStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REVISION_REQUESTED = 'revision_requested';
    case ARCHIVED = 'archived';
}