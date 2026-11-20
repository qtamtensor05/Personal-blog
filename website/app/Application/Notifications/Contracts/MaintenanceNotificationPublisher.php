<?php

namespace App\Application\Notifications\Contracts;

interface MaintenanceNotificationPublisher
{
    public function publish(string $message, int $revision): void;
}
