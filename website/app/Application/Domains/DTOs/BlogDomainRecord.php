<?php

declare(strict_types=1);

namespace App\Application\Domains\DTOs;

use App\Domain\Domains\Enums\DomainStatus;

final readonly class BlogDomainRecord
{
    public function __construct(
        public int $id,
        public int $blogId,
        public string $hostname,
        public DomainStatus $status,
        public string $verificationTokenHash,
        public ?string $verifiedAt,
        public ?string $activatedAt,
    ) {}
}
