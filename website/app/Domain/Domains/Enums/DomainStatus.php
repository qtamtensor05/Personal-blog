<?php

declare(strict_types=1);

namespace App\Domain\Domains\Enums;

enum DomainStatus: string
{
    // Added; waiting for the DNS TXT record.
    case Pending = 'PENDING';
    // TXT record matched; not serving yet.
    case Verified = 'VERIFIED';
    // Serving the blog.
    case Active = 'ACTIVE';
    // Last verification attempt did not find the TXT record.
    case Failed = 'FAILED';
    // Turned off by the owner.
    case Disabled = 'DISABLED';
}
