<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum UserStatus: string
{
    case PendingVerification = 'PENDING_VERIFICATION';
    case Active = 'ACTIVE';
    case Locked = 'LOCKED';
    case Suspended = 'SUSPENDED';
    case Disabled = 'DISABLED';
}
