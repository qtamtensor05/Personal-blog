<?php

declare(strict_types=1);

namespace App\Infrastructure\Blogs\Persistence;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\DTOs\BlogRecord;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use Illuminate\Support\Facades\DB;

final class QueryBuilderBlogRepository implements BlogRepository
{
    private const TABLE = 'blogs';

    public function find(int $id): ?BlogRecord
    {
        $row = DB::table(self::TABLE)->where('id', $id)->whereNull('deleted_at')->first();

        if ($row === null) {
            return null;
        }

        return $this->toDomain($row);
    }

    public function findBySlug(string $slug): ?BlogRecord
    {
        $row = DB::table(self::TABLE)->where('slug', $slug)->whereNull('deleted_at')->first();

        return $row === null ? null : $this->toDomain($row);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        // Unique index u_0 also covers soft-deleted rows.
        $query = DB::table(self::TABLE)->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function ownedBy(int $userId): array
    {
        return DB::table(self::TABLE)
            ->where('owner_user_id', $userId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function create(
        int $ownerUserId,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): int {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'owner_user_id' => $ownerUserId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status->value,
            'visibility' => $visibility->value,
            'discoverable' => $discoverable,
            'published_at' => $status === BlogStatus::Published ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function update(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): void {
        $existing = DB::table(self::TABLE)
            ->where('id', $id)
            ->first(['status', 'published_at']);

        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'status' => $status->value,
                'visibility' => $visibility->value,
                'discoverable' => $discoverable,
                'published_at' => $status === BlogStatus::Published && $existing->published_at === null
                        ? now()
                        : $existing->published_at,
                'updated_at' => now(),
            ]);
    }

    public function softDelete(int $id): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    private function toDomain(object $row): BlogRecord
    {
        return new BlogRecord(
            id: (int) $row->id,
            ownerUserId: (int) $row->owner_user_id,
            name: $row->name,
            slug: $row->slug,
            description: $row->description,
            status: BlogStatus::from($row->status),
            visibility: BlogVisibility::from($row->visibility),
            discoverable: (bool) $row->discoverable,
            publishedAt: $row->published_at,
            deletedAt: $row->deleted_at,
        );
    }
}
