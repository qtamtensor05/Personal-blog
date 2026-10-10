<?php

namespace App\Shared\Contracts;

interface AuditRecorder
{
    public function record(
        ?int $actorId,
        string $action,
        string $resourceType,
        string|int|null $resourceId,
        ?int $blogId = null,
        array $before = [],
        array $after = [],
    ): void;
}
