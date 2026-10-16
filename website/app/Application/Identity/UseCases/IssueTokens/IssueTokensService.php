<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\IssueTokens;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\DTOs\AuthTokens;
use App\Application\Identity\Exceptions\AccountNotActive;
use App\Application\Identity\Exceptions\InvalidCredentials;
use App\Application\Identity\Services\TokenPairFactory;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DomainException;

final readonly class IssueTokensService implements IssueTokensUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private TokenPairFactory $tokenPairs,
    ) {}

    public function execute(
        string $email,
        string $password,
        ?string $ipAddress,
        ?string $userAgent,
    ): AuthTokens {
        try {
            $emailAddress = new EmailAddress($email);
        } catch (DomainException) {
            throw new InvalidCredentials;
        }

        $user = $this->users->findByEmail($emailAddress);
        $passwordHash = $user === null ? null : $this->users->passwordHashFor($user->id());

        if ($passwordHash === null || ! $this->passwordHasher->verify($password, $passwordHash)) {
            throw new InvalidCredentials;
        }

        // Same rule as the web login: only active, verified accounts get tokens.
        if (! $user->isActive() || $user->emailVerifiedAt() === null) {
            throw new AccountNotActive;
        }

        return $this->tokenPairs->create($user->id(), $ipAddress, $userAgent);
    }
}
