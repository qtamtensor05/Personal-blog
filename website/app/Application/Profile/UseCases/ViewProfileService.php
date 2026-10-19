<?php

declare(strict_types=1);

namespace App\Application\Profile\UseCases;

use App\Application\Profile\Contracts\ProfileRepository;
use App\Application\Profile\DTOs\ProfileRecord;
use DomainException;

final readonly class ViewProfileService
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(int $userId): ProfileRecord
    {
        return $this->profiles->find($userId) ?? throw new DomainException('Profile not found.');
    }
}
