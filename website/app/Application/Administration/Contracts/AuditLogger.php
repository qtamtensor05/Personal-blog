<?php

declare(strict_types=1);

namespace App\Application\Administration\Contracts;

/**
 * Writes admin_audit_logs. Request metadata (IP, request ID) is added by the implementation.
 */
interface AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        int $actorId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        ?array $before = null,
        ?array $after = null,
        ?int $blogId = null,
    ): void;
}
