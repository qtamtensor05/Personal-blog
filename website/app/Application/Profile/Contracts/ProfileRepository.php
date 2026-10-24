<?php

declare(strict_types=1);

namespace App\Application\Profile\Contracts;

use App\Application\Profile\DTOs\ProfileRecord;

interface ProfileRepository
{
    public function find(int $userId): ?ProfileRecord;

    public function passwordHash(int $userId): ?string;

    public function updateName(int $userId, string $name): void;

    public function updatePassword(int $userId, string $passwordHash): void;

    public function updateAvatar(int $userId, ?int $mediaId): void;
}
