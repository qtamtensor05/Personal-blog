<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Profile\Contracts\ProfileRepository;
use App\Application\Profile\DTOs\ProfileRecord;
use App\Application\Profile\DTOs\PublicProfileRecord;
use App\Application\Profile\UseCases\ChangeProfilePasswordService;
use App\Application\Profile\UseCases\UpdateProfileAvatarService;
use App\Application\Profile\UseCases\UpdateProfileNameService;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use DomainException;
use PHPUnit\Framework\TestCase;

final class ProfileServicesTest extends TestCase
{
    public function test_it_updates_the_display_name(): void
    {
        $repository = new FakeProfileRepository;

        (new UpdateProfileNameService($repository))->execute(7, '  New Name  ');

        self::assertSame(7, $repository->updatedUserId);
        self::assertSame('New Name', $repository->name);
    }

    public function test_it_requires_the_current_password(): void
    {
        $service = new ChangeProfilePasswordService(
            new FakeProfileRepository,
            new FakeProfilePasswordHasher,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('current password is incorrect');

        $service->execute(7, 'wrong-password', 'new-password');
    }

    public function test_it_hashes_and_updates_a_new_password(): void
    {
        $repository = new FakeProfileRepository;
        $service = new ChangeProfilePasswordService($repository, new FakeProfilePasswordHasher);

        $service->execute(7, 'current-password', 'new-password');

        self::assertSame('hashed:new-password', $repository->newPasswordHash);
    }

    public function test_it_accepts_only_the_users_own_ready_avatar(): void
    {
        $repository = new FakeProfileRepository;
        $service = new UpdateProfileAvatarService($repository);
        $asset = new MediaAssetRecord(
            id: 12,
            scope: MediaScope::User,
            blogId: null,
            uploadedBy: 7,
            publicId: 'avatar',
            secureUrl: 'https://example.com/avatar.jpg',
            width: 200,
            height: 200,
            bytes: 1000,
            altText: 'Profile image',
            status: MediaStatus::Ready,
        );

        $service->execute(7, $asset);

        self::assertSame(12, $repository->avatarMediaId);
    }

    public function test_it_rejects_another_users_avatar(): void
    {
        $service = new UpdateProfileAvatarService(new FakeProfileRepository);
        $asset = new MediaAssetRecord(
            id: 12,
            scope: MediaScope::User,
            blogId: null,
            uploadedBy: 99,
            publicId: 'avatar',
            secureUrl: 'https://example.com/avatar.jpg',
            width: null,
            height: null,
            bytes: null,
            altText: null,
            status: MediaStatus::Ready,
        );

        $this->expectException(DomainException::class);

        $service->execute(7, $asset);
    }
}

final class FakeProfileRepository implements ProfileRepository
{
    public int $updatedUserId = 0;

    public ?string $name = null;

    public ?string $newPasswordHash = null;

    public ?int $avatarMediaId = null;

    public function find(int $userId): ?ProfileRecord
    {
        return new ProfileRecord($userId, 'User', 'user@example.com', null, null);
    }

    public function findPublic(int $userId): ?PublicProfileRecord
    {
        return new PublicProfileRecord($userId, 'User', null);
    }

    public function passwordHash(int $userId): ?string
    {
        return 'hashed:current-password';
    }

    public function updateName(int $userId, string $name): void
    {
        $this->updatedUserId = $userId;
        $this->name = $name;
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->updatedUserId = $userId;
        $this->newPasswordHash = $passwordHash;
    }

    public function updateAvatar(int $userId, ?int $mediaId): void
    {
        $this->updatedUserId = $userId;
        $this->avatarMediaId = $mediaId;
    }
}

final class FakeProfilePasswordHasher implements PasswordHasher
{
    public function hash(string $plainText): string
    {
        return 'hashed:'.$plainText;
    }

    public function verify(string $plainText, string $hash): bool
    {
        return hash_equals($hash, 'hashed:'.$plainText);
    }
}
