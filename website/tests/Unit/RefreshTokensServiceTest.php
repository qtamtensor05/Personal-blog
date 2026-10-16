<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\Contracts\AccessTokenIssuer;
use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\DTOs\IssuedAccessToken;
use App\Application\Identity\DTOs\RefreshTokenRecord;
use App\Application\Identity\Exceptions\InvalidRefreshToken;
use App\Application\Identity\Services\TokenPairFactory;
use App\Application\Identity\UseCases\RefreshTokens\RefreshTokensService;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RefreshTokensServiceTest extends TestCase
{
    private InMemoryRefreshTokenRepository $tokens;

    private InMemoryActiveUserRepository $users;

    private RefreshTokensService $service;

    private TokenPairFactory $factory;

    protected function setUp(): void
    {
        $this->tokens = new InMemoryRefreshTokenRepository;
        $this->users = new InMemoryActiveUserRepository;
        $this->factory = new TokenPairFactory(new StubAccessTokenIssuer, $this->tokens, 14);
        $this->service = new RefreshTokensService($this->tokens, $this->users, $this->factory);
    }

    public function test_it_rotates_the_refresh_token(): void
    {
        $first = $this->factory->create(1, null, null);

        $second = $this->service->execute($first->refreshToken, null, null);

        self::assertNotSame($first->refreshToken, $second->refreshToken);
        self::assertNotNull($this->tokens->rows[1]['revoked_at']);
        self::assertSame(2, $this->tokens->rows[1]['replaced_by_id']);
        self::assertNull($this->tokens->rows[2]['revoked_at']);
    }

    public function test_reusing_a_rotated_token_revokes_all_sessions(): void
    {
        $first = $this->factory->create(1, null, null);
        $this->service->execute($first->refreshToken, null, null);

        try {
            $this->service->execute($first->refreshToken, null, null);
            self::fail('Expected InvalidRefreshToken.');
        } catch (InvalidRefreshToken) {
        }

        self::assertNotNull($this->tokens->rows[2]['revoked_at']);
    }

    public function test_it_rejects_an_unknown_token(): void
    {
        $this->expectException(InvalidRefreshToken::class);

        $this->service->execute('does-not-exist', null, null);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $first = $this->factory->create(1, null, null);
        $this->tokens->rows[1]['expires_at'] = new DateTimeImmutable('-1 minute');

        $this->expectException(InvalidRefreshToken::class);

        $this->service->execute($first->refreshToken, null, null);
    }

    public function test_it_rejects_tokens_of_a_suspended_user(): void
    {
        $first = $this->factory->create(1, null, null);
        $this->users->status = UserStatus::Suspended;

        try {
            $this->service->execute($first->refreshToken, null, null);
            self::fail('Expected InvalidRefreshToken.');
        } catch (InvalidRefreshToken) {
        }

        self::assertCount(1, $this->tokens->rows);
    }
}

final class StubAccessTokenIssuer implements AccessTokenIssuer
{
    public function issue(int $userId): IssuedAccessToken
    {
        return new IssuedAccessToken("access-{$userId}", new DateTimeImmutable('+15 minutes'));
    }

    public function verify(string $token): int
    {
        return (int) substr($token, 7);
    }
}

final class InMemoryRefreshTokenRepository implements RefreshTokenRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function create(
        int $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): int {
        $id = count($this->rows) + 1;
        $this->rows[$id] = [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'replaced_by_id' => null,
        ];

        return $id;
    }

    public function findByHash(string $tokenHash): ?RefreshTokenRecord
    {
        foreach ($this->rows as $id => $row) {
            if ($row['token_hash'] === $tokenHash) {
                return new RefreshTokenRecord(
                    $id,
                    $row['user_id'],
                    $row['expires_at'],
                    $row['revoked_at'],
                    $row['replaced_by_id'],
                );
            }
        }

        return null;
    }

    public function claim(int $id, DateTimeImmutable $revokedAt): bool
    {
        if ($this->rows[$id]['revoked_at'] !== null) {
            return false;
        }

        $this->rows[$id]['revoked_at'] = $revokedAt;

        return true;
    }

    public function linkReplacement(int $id, int $replacedById): void
    {
        $this->rows[$id]['replaced_by_id'] = $replacedById;
    }

    public function revokeAllForUser(int $userId, DateTimeImmutable $revokedAt): void
    {
        foreach ($this->rows as $id => $row) {
            if ($row['user_id'] === $userId && $row['revoked_at'] === null) {
                $this->rows[$id]['revoked_at'] = $revokedAt;
            }
        }
    }
}

final class InMemoryActiveUserRepository implements UserRepository
{
    public UserStatus $status = UserStatus::Active;

    public function findById(int $id): ?User
    {
        return new User(
            $id,
            'Test User',
            new EmailAddress('user@example.com'),
            $this->status,
            new DateTimeImmutable('-1 day'),
        );
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        return null;
    }

    public function create(string $name, EmailAddress $email, string $passwordHash): User
    {
        return new User(1, $name, $email, UserStatus::PendingVerification);
    }

    public function update(User $user): void {}

    public function passwordHashFor(int $userId): ?string
    {
        return null;
    }
}
