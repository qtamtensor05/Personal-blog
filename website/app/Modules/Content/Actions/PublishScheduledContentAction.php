<?php

namespace App\Modules\Content\Actions;

use App\Modules\Content\Enums\ContentStatus;
use App\Modules\Content\Support\PostTermSynchronizer;
use App\Modules\Content\Types\ContentType;
use App\Modules\Content\Types\ContentTypeRegistry;
use App\Modules\Media\MediaReferenceGuard;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class PublishScheduledContentAction
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly ContentTypeRegistry $types,
        private readonly PostTermSynchronizer $terms,
        private readonly MediaReferenceGuard $media,
        private readonly AuditRecorder $audit,
    ) {}

    public function execute(): int
    {
        $published = 0;

        foreach ($this->types->all() as $type) {
            foreach ($this->dueIds($type) as $id) {
                $published += (int) DB::transaction(fn (): bool => $this->publishOne($type, (int) $id));
            }
        }

        return $published;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function dueIds(ContentType $type)
    {
        $query = DB::table("{$type->table()} as content")
            ->join('blogs as blog', 'blog.id', '=', 'content.blog_id')
            ->join('users as owner', 'owner.id', '=', 'blog.owner_user_id')
            ->whereIn('content.status', $this->publishableStatuses())
            ->where('content.scheduled_at', '<=', now())
            ->whereNull('content.deleted_at');

        return $this->restrictToActiveOwners($query, 'blog', 'owner')
            ->orderBy('content.id')
            ->limit(self::BATCH_SIZE)
            ->pluck('content.id');
    }

    private function publishOne(ContentType $type, int $id): bool
    {
        $row = DB::table($type->table())->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();

        if (
            ! $row
            || ! in_array($row->status, $this->publishableStatuses(), true)
            || ! $row->scheduled_at
            || now()->isBefore($row->scheduled_at)
        ) {
            return false;
        }

        $ownerId = $this->activeOwnerId((int) $row->blog_id);

        if (! $ownerId) {
            return false;
        }

        $draft = json_decode($row->draft_data ?? '{}', true);
        $changes = array_intersect_key($draft, array_flip($type->draftFields()));

        try {
            $this->media->assertReadyForBlog((int) $row->blog_id, array_replace((array) $row, $changes));
        } catch (ValidationException) {
            return $this->blockFailedSchedule($type, $row, $ownerId, 'invalid_media');
        }

        if ($type->supportsTerms()) {
            $invalidTerms = $this->firstInvalidTermTable((int) $row->blog_id, $draft);

            if ($invalidTerms !== null) {
                return $this->blockFailedSchedule($type, $row, $ownerId, 'invalid_'.$invalidTerms);
            }

            $this->replaceDraftTerms((int) $row->blog_id, $id, $draft);
        }

        DB::table($type->table())->where('id', $id)->update($changes + [
            'status' => ContentStatus::Published->value,
            'scheduled_at' => null,
            'draft_data' => null,
            'published_at' => $row->published_at ?: now(),
            'updated_at' => now(),
        ]);

        $this->audit->record($ownerId, $type->singular().'.scheduled_published', $type->singular(), $id, $row->blog_id);

        return true;
    }

    /**
     * @return list<string>
     */
    private function publishableStatuses(): array
    {
        return [ContentStatus::Scheduled->value, ContentStatus::Published->value];
    }

    private function activeOwnerId(int $blogId): ?int
    {
        $query = DB::table('blogs')
            ->join('users', 'users.id', '=', 'blogs.owner_user_id')
            ->where('blogs.id', $blogId);

        $ownerId = $this->restrictToActiveOwners($query, 'blogs', 'users')->value('users.id');

        return $ownerId ? (int) $ownerId : null;
    }

    /**
     * Only published blogs owned by an active, verified, unlocked account may
     * have scheduled content go live.
     */
    private function restrictToActiveOwners(Builder $query, string $blog, string $owner): Builder
    {
        return $query
            ->where("{$blog}.status", 'PUBLISHED')
            ->where("{$owner}.status", 'ACTIVE')
            ->whereNotNull("{$owner}.email_verified_at")
            ->whereNull("{$owner}.deleted_at")
            ->whereNull("{$blog}.deleted_at")
            ->where(fn ($query) => $query
                ->whereNull("{$owner}.locked_until")
                ->orWhere("{$owner}.locked_until", '<=', now()));
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function firstInvalidTermTable(int $blogId, array $draft): ?string
    {
        foreach (PostTermSynchronizer::TERMS as $termTable => $definition) {
            $ids = $draft[$definition['draftKey']] ?? null;

            if ($ids !== null && ! $this->terms->allBelongToBlog($blogId, $termTable, $ids)) {
                return $termTable;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function replaceDraftTerms(int $blogId, int $postId, array $draft): void
    {
        foreach (PostTermSynchronizer::TERMS as $termTable => $definition) {
            if (isset($draft[$definition['draftKey']])) {
                $this->terms->replace($blogId, $postId, $termTable, $draft[$definition['draftKey']]);
            }
        }
    }

    /**
     * Content that was never live returns to DRAFT; live content keeps its
     * current public version.
     */
    private function blockFailedSchedule(ContentType $type, object $row, int $ownerId, string $reason): bool
    {
        $newStatus = $row->status === ContentStatus::Scheduled->value
            ? ContentStatus::Draft->value
            : ContentStatus::Published->value;

        DB::table($type->table())
            ->where('id', $row->id)
            ->where('blog_id', $row->blog_id)
            ->update([
                'status' => $newStatus,
                'scheduled_at' => null,
                'updated_at' => now(),
            ]);

        $this->audit->record($ownerId, $type->singular().'.schedule_blocked', $type->singular(), (int) $row->id, (int) $row->blog_id);

        Log::warning('Scheduled content blocked', [
            'type' => $type->table(),
            'content_id' => $row->id,
            'blog_id' => $row->blog_id,
            'reason' => $reason,
        ]);

        return false;
    }
}
