<?php

declare(strict_types=1);

namespace App\Application\Blogs\Contracts;

use App\Application\Blogs\DTOs\BlogRecord;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

interface BlogRepository
{
    public function find(int $id): ?BlogRecord;

    public function slugExists(string $slug, ?int $exceptId = null): bool;

    /**
     * All blogs the user owns.
     *
     * @return list<BlogRecord>
     */
    public function ownedBy(int $userId): array;

    public function create(
        int $ownerUserId,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): int;

    public function update(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): void;

    public function softDelete(int $id): void;
}
