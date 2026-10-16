<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Content\Enums\ContentStatus;
use App\Modules\Content\Enums\SaveIntent;
use App\Modules\Content\Support\ContentInputPreparer;
use App\Modules\Content\Support\PostTermSynchronizer;
use App\Modules\Content\Types\ContentType;
use App\Modules\Content\Types\ContentTypeRegistry;
use App\Modules\Media\MediaReferenceGuard;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Support\JsonFingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaveContentAction
{
    public function __construct(
        private readonly ContentTypeRegistry $types,
        private readonly OwnerContext $owners,
        private readonly ContentInputPreparer $inputs,
        private readonly MediaReferenceGuard $media,
        private readonly PostTermSynchronizer $terms,
        private readonly AuditRecorder $audit,
    ) {}

    public static function fingerprint(object $row): string
    {
        return JsonFingerprint::make((array) $row);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $actor, int $blog, string $kind, ?int $id, array $input, ?string $fingerprint, string $intent = 'draft'): int
    {
        $type = $this->types->for($kind);
        $this->owners->resolve($actor, $blog, true);
        [$data, $intent] = $this->inputs->prepare($blog, $id === null, $input, $intent);

        return DB::transaction(function () use ($actor, $blog, $type, $id, $data, $fingerprint, $intent): int {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            $this->owners->resolve($actor, $blog, true);

            $row = $id ? $this->lockRow($type, $blog, $id) : null;
            abort_if($id && ! $row, 404);

            $this->assertNotStale($row, $fingerprint);
            $this->assertSlugAvailable($type, $blog, $data['slug'], $id);
            $this->media->assertReadyForBlog($blog, $data);

            $id = $this->persist($actor, $blog, $type, $id, $row, $data, $intent);
            $this->applyStatus($type, $blog, $id, $row, $intent);

            if ($type->supportsTerms()) {
                $this->syncTerms($blog, $id, $row, $data, $intent);
            }

            $this->audit->record($actor->id, $type->singular().'.'.$intent->value, $type->singular(), $id, $blog);

            return $id;
        });
    }

    private function lockRow(ContentType $type, int $blog, int $id): ?object
    {
        return DB::table($type->table())
            ->where(['id' => $id, 'blog_id' => $blog])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();
    }

    private function assertNotStale(?object $row, ?string $fingerprint): void
    {
        if ($row && ! hash_equals(self::fingerprint($row), $fingerprint ?? '')) {
            throw ValidationException::withMessages([
                'conflict' => 'Nội dung đã thay đổi. Hãy tải lại trước khi lưu; nội dung bạn đang sửa vẫn được giữ trên màn hình.',
            ]);
        }
    }

    private function assertSlugAvailable(ContentType $type, int $blog, string $slug, ?int $ignoreId): void
    {
        $taken = DB::table($type->table())
            ->where('blog_id', $blog)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được sử dụng trong blog.']);
        }
    }

    /**
     * Insert a new row, update a row directly, or stash the edit as a draft
     * snapshot when the row is already live and the editor is not publishing.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(User $actor, int $blog, ContentType $type, ?int $id, ?object $row, array $data, SaveIntent $intent): int
    {
        $isPublished = $row?->status === ContentStatus::Published->value;
        $values = $this->columnValues($type, $data, $isPublished);
        $meta = $this->metadata($actor, $row, $data, $intent);
        $where = ['id' => $id, 'blog_id' => $blog];

        if (! $row) {
            $type->assertCanCreate($blog);

            return DB::table($type->table())->insertGetId([
                ...$values,
                ...$meta,
                ...$type->creationAttributes($actor, $data),
                'blog_id' => $blog,
                'status' => ContentStatus::Draft->value,
                'created_at' => now(),
            ]);
        }

        if ($isPublished && $intent !== SaveIntent::Publish) {
            $snapshot = $values + ($type->supportsTerms()
                ? array_intersect_key($data, array_flip(['category_ids', 'tag_ids']))
                : []);

            DB::table($type->table())->where($where)->update([
                ...$meta,
                'draft_data' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            ]);
        } else {
            DB::table($type->table())->where($where)->update([...$values, ...$meta, 'draft_data' => null]);
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columnValues(ContentType $type, array $data, bool $isPublished): array
    {
        $values = array_intersect_key($data, array_flip(['slug', ...$type->draftFields()]));

        // A live slug is immutable so public URLs never break.
        if ($isPublished) {
            unset($values['slug']);
        }

        $values['content_json'] = json_encode($values['content_json'], JSON_THROW_ON_ERROR);

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function metadata(User $actor, ?object $row, array $data, SaveIntent $intent): array
    {
        $meta = [
            'updated_by' => $actor->id,
            'updated_at' => now(),
            'scheduled_at' => $intent === SaveIntent::Schedule ? $data['scheduled_at'] : null,
        ];

        // Editing a scheduled row without rescheduling it returns it to draft.
        if ($row?->status === ContentStatus::Scheduled->value && $intent !== SaveIntent::Schedule) {
            $meta['status'] = ContentStatus::Draft->value;
        }

        return $meta;
    }

    private function applyStatus(ContentType $type, int $blog, int $id, ?object $row, SaveIntent $intent): void
    {
        $query = DB::table($type->table())->where(['id' => $id, 'blog_id' => $blog]);

        if ($intent === SaveIntent::Publish) {
            $query->update([
                'status' => ContentStatus::Published->value,
                'draft_data' => null,
                'scheduled_at' => null,
                'published_at' => $row?->published_at ?: now(),
            ]);
        } elseif ($intent === SaveIntent::Schedule && $row?->status !== ContentStatus::Published->value) {
            $query->update(['status' => ContentStatus::Scheduled->value]);
        }
    }

    /**
     * Terms must always belong to the blog, but relations are live metadata:
     * they are only rewritten when the edit goes live (or the row is not live).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncTerms(int $blog, int $postId, ?object $row, array $data, SaveIntent $intent): void
    {
        $rewrite = $row?->status !== ContentStatus::Published->value || $intent === SaveIntent::Publish;

        foreach (PostTermSynchronizer::TERMS as $termTable => $definition) {
            $ids = $data[$definition['draftKey']] ?? [];

            if (! $this->terms->allBelongToBlog($blog, $termTable, $ids)) {
                throw ValidationException::withMessages([$definition['draftKey'] => 'Phân loại không thuộc blog.']);
            }

            if ($rewrite) {
                $this->terms->replace($blog, $postId, $termTable, $ids);
            }
        }
    }
}
