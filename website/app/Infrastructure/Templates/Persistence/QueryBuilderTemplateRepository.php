<?php

declare(strict_types=1);

namespace App\Infrastructure\Templates\Persistence;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\DTOs\TemplateRecord;
use App\Domain\Templates\Enums\TemplateStatus;
use App\Domain\Templates\ValueObjects\TemplateData;
use Illuminate\Support\Facades\DB;

final class QueryBuilderTemplateRepository implements TemplateRepository
{
    private const TABLE = 'templates';

    public function __construct(private readonly TemplateDataHydrator $hydrator) {}

    public function find(int $id): ?TemplateRecord
    {
        $row = DB::table(self::TABLE)->where('id', $id)->whereNull('deleted_at')->first();

        if ($row === null) {
            return null;
        }

        return $this->toDomain($row);
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

    public function create(
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        int $createdBy,
        TemplateData $draftData,
    ): int {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => TemplateStatus::Draft->value,
            'preview_media_id' => $previewMediaId,
            'demo_url' => $demoUrl,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'version_number' => 0,
            'draft_data' => json_encode($draftData),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updateDraft(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        int $updatedBy,
        TemplateData $draftData,
    ): void {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'preview_media_id' => $previewMediaId,
                'demo_url' => $demoUrl,
                'updated_by' => $updatedBy,
                'draft_data' => json_encode($draftData),
                'updated_at' => now(),
            ]);
    }

    public function publish(int $id, int $publishedBy, TemplateStatus $status): void
    {
        $row = DB::table(self::TABLE)
            ->where('id', $id)
            ->first(['draft_data', 'version_number']);

        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'template_data' => $row->draft_data,
                'status' => $status->value,
                'published_by' => $publishedBy,
                'published_at' => now(),
                'version_number' => (int) $row->version_number + 1,
                'updated_at' => now(),
            ]);
    }

    public function setStatus(int $id, TemplateStatus $status): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update(['status' => $status->value, 'updated_at' => now()]);
    }

    public function softDelete(int $id): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function all(): array
    {
        return DB::table(self::TABLE)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function allDeleted(): array
    {
        return DB::table(self::TABLE)
            ->whereNotNull('deleted_at')
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function published(): array
    {
        return DB::table(self::TABLE)
            ->where('status', TemplateStatus::Published->value)
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn ($row) => $this->toDomain($row))
            ->all();
    }

    public function restore(int $id): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->whereNotNull('deleted_at')
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function forceDelete(int $id): void
    {
        DB::table(self::TABLE)->where('id', $id)->delete();
    }

    private function toDomain(object $row): TemplateRecord
    {
        return new TemplateRecord(
            id: (int) $row->id,
            name: $row->name,
            slug: $row->slug,
            description: $row->description,
            status: TemplateStatus::from($row->status),
            previewMediaId: $row->preview_media_id !== null ? (int) $row->preview_media_id : null,
            demoUrl: $row->demo_url,
            createdBy: (int) $row->created_by,
            updatedBy: (int) $row->updated_by,
            publishedBy: $row->published_by !== null ? (int) $row->published_by : null,
            publishedAt: $row->published_at,
            deletedAt: $row->deleted_at,
            versionNumber: (int) $row->version_number,
            publishedData: $row->template_data !== null
                ? $this->hydrator->fromJson($row->template_data)
                : null,
            draftData: $row->draft_data !== null
                ? $this->hydrator->fromJson($row->draft_data)
                : null,
        );
    }
}
