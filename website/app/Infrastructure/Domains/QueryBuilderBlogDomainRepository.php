<?php

declare(strict_types=1);

namespace App\Infrastructure\Domains;

use App\Application\Domains\Contracts\BlogDomainRepository;
use App\Application\Domains\DTOs\BlogDomainRecord;
use App\Domain\Domains\Enums\DomainStatus;
use Illuminate\Support\Facades\DB;

final class QueryBuilderBlogDomainRepository implements BlogDomainRepository
{
    private const TABLE = 'blog_domains';

    public function forBlog(int $blogId): array
    {
        return DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->orderBy('hostname')
            ->get()
            ->map(fn ($row) => $this->toRecord($row))
            ->all();
    }

    public function find(int $blogId, int $id): ?BlogDomainRecord
    {
        $row = DB::table(self::TABLE)->where('blog_id', $blogId)->where('id', $id)->first();

        return $row === null ? null : $this->toRecord($row);
    }

    public function hostnameExists(string $hostname): bool
    {
        return DB::table(self::TABLE)->where('hostname', $hostname)->exists();
    }

    public function create(int $blogId, string $hostname, string $tokenHash): int
    {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'blog_id' => $blogId,
            'hostname' => $hostname,
            'status' => DomainStatus::Pending->value,
            'verification_token_hash' => $tokenHash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function setStatus(int $blogId, int $id, DomainStatus $status): void
    {
        $now = now();
        $values = ['status' => $status->value, 'updated_at' => $now];

        if ($status === DomainStatus::Verified) {
            $values['verified_at'] = $now;
        }

        if ($status === DomainStatus::Active) {
            $values['activated_at'] = $now;
        }

        DB::table(self::TABLE)->where('blog_id', $blogId)->where('id', $id)->update($values);
    }

    public function delete(int $blogId, int $id): void
    {
        DB::table(self::TABLE)->where('blog_id', $blogId)->where('id', $id)->delete();
    }

    public function activeBlogSlugForHost(string $hostname): ?string
    {
        $slug = DB::table(self::TABLE.' as d')
            ->join('blogs as b', 'b.id', '=', 'd.blog_id')
            ->where('d.hostname', $hostname)
            ->where('d.status', DomainStatus::Active->value)
            ->whereNull('b.deleted_at')
            ->value('b.slug');

        return $slug === null ? null : (string) $slug;
    }

    private function toRecord(object $row): BlogDomainRecord
    {
        return new BlogDomainRecord(
            id: (int) $row->id,
            blogId: (int) $row->blog_id,
            hostname: $row->hostname,
            status: DomainStatus::from($row->status),
            verificationTokenHash: $row->verification_token_hash,
            verifiedAt: $row->verified_at,
            activatedAt: $row->activated_at,
        );
    }
}
