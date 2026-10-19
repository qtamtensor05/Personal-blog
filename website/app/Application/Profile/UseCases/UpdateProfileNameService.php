<?php

declare(strict_types=1);

namespace App\Application\Profile\UseCases;

use App\Application\Profile\Contracts\ProfileRepository;
use DomainException;

final readonly class UpdateProfileNameService
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(int $userId, string $name): void
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 160) {
            throw new DomainException('Name must contain between 1 and 160 characters.');
        }

        $this->profiles->updateName($userId, $name);
    }
}
