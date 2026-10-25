<?php

declare(strict_types=1);

namespace App\Application\Comments;

use App\Domain\Comments\Enums\CommentStatus;
use Illuminate\Support\Facades\DB;

final readonly class CommentTreeReader
{
    public function __construct(private CommentPermissions $permissions) {}

    public function roots(int $postId, int $ownerId, ?object $viewer, int $page = 1): array
    {
        return $this->read($postId, $ownerId, null, $viewer, $page, 15);
    }

    public function replies(int $postId, int $ownerId, int $parentId, ?object $viewer, int $page = 1): array
    {
        abort_unless(DB::table('comments')->where('id', $parentId)->where('post_id', $postId)->exists(), 404);

        return $this->read($postId, $ownerId, $parentId, $viewer, $page, 10);
    }

    private function read(int $postId, int $ownerId, ?int $parentId, ?object $viewer, int $page, int $perPage): array
    {
        $lineage = $this->readableLineage($postId);
        $isModerator = $this->permissions->isModerator($viewer);
        $isOwner = $this->permissions->isPostOwner($viewer, $ownerId);
        $query = DB::table('comments as c')->leftJoin('users as u', 'u.id', '=', 'c.user_id')->where('c.post_id', $postId)
            ->when($parentId === null, fn ($q) => $q->whereNull('c.parent_id'), fn ($q) => $q->where('c.parent_id', $parentId))
            ->where(function ($q) use ($lineage, $isOwner, $isModerator): void {
                $q->whereIn('c.id', $lineage);
                if ($isOwner) {
                    $q->orWhere('c.status', CommentStatus::OwnerHidden->value);
                }
                if ($isModerator) {
                    $q->orWhereNotNull('c.id');
                }
            });
        $total = (clone $query)->count('c.id');
        $rows = $query->select(['c.id', 'c.parent_id', 'c.body', 'c.status', 'c.user_id', 'c.deleted_at', 'c.created_at', 'u.name as author_name'])
            ->selectSub(function ($q) use ($isOwner, $isModerator) {
                $q->from('comments as r')->whereColumn('r.parent_id', 'c.id')->whereNull('r.deleted_at');
                if (! $isModerator) {
                    $q->where(function ($status) use ($isOwner): void {
                        $status->where('r.status', CommentStatus::Visible->value);
                        if ($isOwner) {
                            $status->orWhere('r.status', CommentStatus::OwnerHidden->value);
                        }
                    });
                }
                $q->selectRaw('count(*)');

                return $q;
            }, 'replies_count')
            ->orderBy('c.created_at')->orderBy('c.id')->forPage(max(1, $page), $perPage)->get();
        $items = $rows->map(function (object $row) use ($viewer, $isModerator, $isOwner): array {
            $canRead = $row->deleted_at === null && ($isModerator || $row->status === CommentStatus::Visible->value || ($row->status === CommentStatus::OwnerHidden->value && $isOwner));

            $isVisible = $row->status === CommentStatus::Visible->value && $row->deleted_at === null;
            $isOwnerHidden = $row->status === CommentStatus::OwnerHidden->value && $row->deleted_at === null;
            $canManageAsOwner = $isOwner && $isVisible;

            return [
                'id' => (int) $row->id,
                'parent_id' => $row->parent_id === null ? null : (int) $row->parent_id,
                'body' => $canRead ? (string) $row->body : null,
                'author_name' => $canRead ? (string) ($row->author_name ?? 'Thành viên') : null,
                'created_at' => (string) $row->created_at,
                'replies_count' => (int) $row->replies_count,
                'placeholder' => ! $canRead,
                'status' => $row->status,
                'is_owner_hidden' => $isOwnerHidden,
                'is_admin_hidden' => $row->status === CommentStatus::AdminHidden->value,
                'can_reply' => $viewer !== null && $isVisible,
                'can_edit' => $viewer !== null && (int) $row->user_id === (int) $viewer->id && $isVisible,
                'can_delete' => $viewer !== null && (int) $row->user_id === (int) $viewer->id && $row->deleted_at === null,
                'can_hide' => $canManageAsOwner,
                'can_unhide' => $isOwner && $isOwnerHidden,
                'can_report' => $viewer !== null && $canRead,
            ];
        })->all();

        return ['items' => $items, 'total' => $total, 'page' => max(1, $page), 'last_page' => max(1, (int) ceil($total / $perPage))];
    }

    private function readableLineage(int $postId): array
    {
        $rows = DB::select(<<<'SQL'
WITH RECURSIVE lineage AS (
    SELECT id, parent_id FROM comments WHERE post_id = ? AND status = 'VISIBLE' AND deleted_at IS NULL
    UNION DISTINCT
    SELECT parent.id, parent.parent_id FROM comments parent JOIN lineage child ON child.parent_id = parent.id WHERE parent.post_id = ?
)
SELECT id FROM lineage
SQL, [$postId, $postId]);

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
    }
}
