<?php

declare(strict_types=1);

namespace App\Application\Moderation\Contracts;

interface ReportNotificationRecipients
{
    /** @return list<int> Active users authorized to access and manage reports. */
    public function userIds(): array;
}
