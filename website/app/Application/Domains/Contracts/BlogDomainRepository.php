<?php

declare(strict_types=1);

namespace App\Application\Domains\Contracts;

use App\Application\Domains\DTOs\BlogDomainRecord;
use App\Domain\Domains\Enums\DomainStatus;

interface BlogDomainRepository
{
    /** @return list<BlogDomainRecord> */
    public function forBlog(int $blogId): array;

    public function find(int $blogId, int $id): ?BlogDomainRecord;

    /**
     * Hostnames are unique across all blogs (u_0).
     */
    public function hostnameExists(string $hostname): bool;

    public function create(int $blogId, string $hostname, string $tokenHash): int;

    public function setStatus(int $blogId, int $id, DomainStatus $status): void;

    public function delete(int $blogId, int $id): void;

    /**
     * Slug of the non-deleted blog served by an ACTIVE domain.
     */
    public function activeBlogSlugForHost(string $hostname): ?string;
}
