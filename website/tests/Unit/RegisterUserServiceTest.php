<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Contracts\VerificationUrlGenerator;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserService;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailUseCase;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DomainException;
use PHPUnit\Framework\TestCase;

final class RegisterUserServiceTest extends TestCase
{
    public function test_it_registers_a_user_with_a_hashed_password(): void
    {
        $repository = new FakeUserRepository;
        $hasher = new FakePasswordHasher;
        $urlGenerator = new FakeVerificationUrlGenerator;
        $emailSender = new FakeSendVerificationEmail;

        $service = new RegisterUserService($repository, $hasher, $urlGenerator, $emailSender);

        $user = $service->execute('Nguyen Van A', 'user@example.com', 'password123');

        self::assertSame('Nguyen Van A', $user->name());
        self::assertSame(UserStatus::PendingVerification, $user->status());
        self::assertSame('hashed:password123', $repository->passwordHash);
        self::assertSame('password123', $hasher->receivedPassword);
    }

    public function test_it_rejects_an_existing_email(): void
    {
        $repository = new FakeUserRepository;
        $repository->existingUser = new User(
            1,
            'Existing User',
            new EmailAddress('user@example.com'),
            UserStatus::Active,
        );

        $service = new RegisterUserService(
            $repository,
            new FakePasswordHasher,
            new FakeVerificationUrlGenerator,
            new FakeSendVerificationEmail,
        );

        $this->expectException(EmailAlreadyRegistered::class);

        $service->execute('Nguyen Van B', 'user@example.com', 'password123');
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $service = new RegisterUserService(
            new FakeUserRepository,
            new FakePasswordHasher,
            new FakeVerificationUrlGenerator,
            new FakeSendVerificationEmail,
        );

        $this->expectException(DomainException::class);

        $service->execute('Nguyen Van A', 'invalid-email', 'password123');
    }

    public function test_it_rejects_a_short_password(): void
    {
        $service = new RegisterUserService(
            new FakeUserRepository,
            new FakePasswordHasher,
            new FakeVerificationUrlGenerator,
            new FakeSendVerificationEmail,
        );

        $this->expectException(DomainException::class);

        $service->execute('Nguyen Van A', 'user@example.com', 'short');
    }
}

final class FakeUserRepository implements UserRepository
{
    public ?User $existingUser = null;

    public ?string $passwordHash = null;

    public function findById(int $id): ?User
    {
        return null;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        if ($this->existingUser !== null && $this->existingUser->email()->equals($email)) {
            return $this->existingUser;
        }

        return null;
    }

    public function create(string $name, EmailAddress $email, string $passwordHash): User
    {
        $this->passwordHash = $passwordHash;

        return new User(1, $name, $email, UserStatus::PendingVerification);
    }

    public function update(User $user): void {}

    public function passwordHashFor(int $userId): ?string
    {
        return $this->passwordHash;
    }
}

final class FakePasswordHasher implements PasswordHasher
{
    public ?string $receivedPassword = null;

    public function hash(string $plainText): string
    {
        $this->receivedPassword = $plainText;

        return 'hashed:'.$plainText;
    }

    public function verify(string $plainText, string $hash): bool
    {
        return $hash === 'hashed:'.$plainText;
    }
}

final class FakeVerificationUrlGenerator implements VerificationUrlGenerator
{
    public function generate(int $userId): string
    {
        return "https://example.com/verify/{$userId}";
    }
}

final class FakeSendVerificationEmail implements SendVerificationEmailUseCase
{
    public function execute(int $userId, string $verificationUrl): void
    {
        // No-op for testing
    }
}
