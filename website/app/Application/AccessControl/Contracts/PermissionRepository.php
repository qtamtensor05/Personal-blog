<?php

declare(strict_types=1);

namespace App\Application\AccessControl\Contracts;

interface PermissionRepository
{
    /** @return list<string> Effective permission codes for the user. */
    public function codesForUser(int $userId): array;
}
