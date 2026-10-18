<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Persistence;

use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Media\DTOs\StoredMedia;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use Illuminate\Support\Facades\DB;

final class QueryBuilderMediaAssetRepository implements MediaAssetRepository
{
    private const TABLE = 'media_assets';

    /**
     * Every FK column that points at media_assets.id.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const REFERENCES = [
        ['users', 'avatar_media_id'],
        ['blog_settings', 'logo_media_id'],
        ['blog_settings', 'favicon_media_id'],
        ['blog_settings', 'og_media_id'],
        ['templates', 'preview_media_id'],
        ['posts', 'cover_media_id'],
        ['posts', 'og_media_id'],
        ['pages', 'og_media_id'],
    ];

    public function createPending(
        MediaScope $scope,
        ?int $blogId,
        int $uploadedBy,
        string $cloudName,
        string $originalName,
        ?string $mimeType,
        ?string $altText,
    ): int {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'scope' => $scope->value,
            'blog_id' => $blogId,
            'uploaded_by' => $uploadedBy,
            'cloud_name' => $cloudName,
            'original_name' => mb_substr($originalName, 0, 255),
            'mime_type' => $mimeType,
            'alt_text' => $altText,
            'status' => MediaStatus::Uploading->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function markReady(int $id, StoredMedia $stored): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'cloudinary_asset_id' => $stored->assetId,
                'cloudinary_public_id' => $stored->publicId,
                'cloudinary_version' => $stored->version,
                'resource_type' => $stored->resourceType,
                'delivery_type' => $stored->deliveryType,
                'secure_url' => $stored->secureUrl,
                'format' => $stored->format,
                'width' => $stored->width,
                'height' => $stored->height,
                'bytes' => $stored->bytes,
                'status' => MediaStatus::Ready->value,
                'updated_at' => now(),
            ]);
    }

    public function markFailed(int $id): void
    {
        $this->setStatus($id, MediaStatus::Failed);
    }

    public function find(int $id): ?MediaAssetRecord
    {
        $row = DB::table(self::TABLE)->where('id', $id)->whereNull('deleted_at')->first();

        if ($row === null) {
            return null;
        }

        return new MediaAssetRecord(
            id: (int) $row->id,
            scope: MediaScope::from($row->scope),
            blogId: $row->blog_id !== null ? (int) $row->blog_id : null,
            uploadedBy: (int) $row->uploaded_by,
            publicId: $row->cloudinary_public_id,
            secureUrl: $row->secure_url,
            width: $row->width !== null ? (int) $row->width : null,
            height: $row->height !== null ? (int) $row->height : null,
            bytes: $row->bytes !== null ? (int) $row->bytes : null,
            altText: $row->alt_text,
            originalName: $row->original_name,
            mimeType: $row->mime_type,
            resourceType: $row->resource_type,
            status: MediaStatus::from($row->status),
        );
    }

    public function isUsableForBlog(int $id, int $blogId): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->where('scope', MediaScope::Blog->value)
            ->where('blog_id', $blogId)
            ->where('status', MediaStatus::Ready->value)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function readyForBlog(int $blogId): array
    {
        return DB::table(self::TABLE)
            ->where('scope', MediaScope::Blog->value)
            ->where('blog_id', $blogId)
            ->where('status', MediaStatus::Ready->value)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => new MediaAssetRecord(
                id: (int) $row->id,
                scope: MediaScope::from($row->scope),
                blogId: (int) $row->blog_id,
                uploadedBy: (int) $row->uploaded_by,
                publicId: $row->cloudinary_public_id,
                secureUrl: $row->secure_url,
                width: $row->width !== null ? (int) $row->width : null,
                height: $row->height !== null ? (int) $row->height : null,
                bytes: $row->bytes !== null ? (int) $row->bytes : null,
                altText: $row->alt_text,
                originalName: $row->original_name,
                mimeType: $row->mime_type,
                resourceType: $row->resource_type,
                status: MediaStatus::from($row->status),
            ))
            ->all();
    }

    public function readyAssetsForBlog(int $blogId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table(self::TABLE)
            ->whereIn('id', $ids)
            ->where('scope', MediaScope::Blog->value)
            ->where('blog_id', $blogId)
            ->where('status', MediaStatus::Ready->value)
            ->whereNull('deleted_at')
            ->whereNotNull('secure_url')
            ->get(['id', 'secure_url', 'width', 'height'])
            ->mapWithKeys(fn ($row) => [(int) $row->id => [
                'url' => (string) $row->secure_url,
                'width' => $row->width !== null ? (int) $row->width : null,
                'height' => $row->height !== null ? (int) $row->height : null,
            ]])
            ->all();
    }

    public function markDeleting(int $id): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->whereIn('status', [MediaStatus::Ready->value, MediaStatus::Failed->value])
            ->update(['status' => MediaStatus::Deleting->value, 'updated_at' => now()]) === 1;
    }

    public function markDeleted(int $id): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'status' => MediaStatus::Deleted->value,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markReadyAgain(int $id): void
    {
        $this->setStatus($id, MediaStatus::Ready);
    }

    public function usedBytesForBlog(int $blogId): int
    {
        return (int) DB::table(self::TABLE)
            ->where('blog_id', $blogId)
            ->whereNull('deleted_at')
            ->whereIn('status', [MediaStatus::Uploading->value, MediaStatus::Ready->value])
            ->sum('bytes');
    }

    public function quotaBytesForBlog(int $blogId): ?int
    {
        $quota = DB::table('blog_settings')->where('blog_id', $blogId)->value('storage_quota_bytes');

        return $quota === null ? null : (int) $quota;
    }

    public function isReferenced(int $id): bool
    {
        foreach (self::REFERENCES as [$table, $column]) {
            if (DB::table($table)->where($column, $id)->exists()) {
                return true;
            }
        }

        // Images inside Tiptap content have no FK: look for any "mediaId" equal to the ID,
        // in both the live content and the unpublished draft.
        $contains = "JSON_CONTAINS(JSON_EXTRACT(%s, '$**.mediaId'), CAST(? AS JSON))";

        foreach (['posts', 'pages'] as $table) {
            $used = DB::table($table)
                ->whereNull('deleted_at')
                ->where(fn ($q) => $q
                    ->whereRaw(sprintf($contains, 'content_json'), [(string) $id])
                    ->orWhereRaw(sprintf($contains, 'draft_data'), [(string) $id]))
                ->exists();

            if ($used) {
                return true;
            }
        }

        return false;
    }

    private function setStatus(int $id, MediaStatus $status): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update(['status' => $status->value, 'updated_at' => now()]);
    }
}
