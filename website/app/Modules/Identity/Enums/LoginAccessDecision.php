<?php

namespace App\Modules\Identity\Enums;

enum LoginAccessDecision
{
    case VerificationRequired;
    case Denied;
    case Allowed;
}
