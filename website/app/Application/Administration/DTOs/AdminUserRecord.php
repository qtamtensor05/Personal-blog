<?php

declare(strict_types=1);

namespace App\Application\Administration\DTOs;

use App\Domain\Identity\Enums\UserStatus;

final readonly class AdminUserRecord
{
    /**
     * @param  list<array{role_id: int, code: string, name: string, expires_at: ?string}>  $roles
     * @param  list<array{id: int, kind: string, reason: string, starts_at: string, ends_at: ?string}>  $activeRestrictions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public UserStatus $status,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public string $createdAt,
        public array $roles = [],
        public array $activeRestrictions = [],
    ) {}
}
