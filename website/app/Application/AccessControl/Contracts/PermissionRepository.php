<?php

declare(strict_types=1);

namespace App\Application\AccessControl\Contracts;

interface PermissionRepository
{
    /**
     * Permission codes granted to the user through non-expired platform roles.
     *
     * @return list<string>
     */
    public function codesForUser(int $userId): array;
}
