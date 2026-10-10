<?php

namespace App\Modules\Design\Actions;

use App\Models\User;
use App\Modules\PublicSite\Rendering\SafeSectionRegistry;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Support\JsonFingerprint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublishDesignAction
{
    public function __construct(
        private readonly SafeSectionRegistry $registry,
        private readonly AuditRecorder $audit,
    ) {}

    public function execute(User $actor, int $blogId, string $fingerprint): void
    {
        DB::transaction(function () use ($actor, $blogId, $fingerprint): void {
            $blog = DB::table('blogs')->where('id', $blogId)->whereNull('deleted_at')->lockForUpdate()->first();
            if (
                ! $blog ||
                (int) $blog->owner_user_id !== (int) $actor->id ||
                ! $actor->canAccessApplication() ||
                $blog->status === 'SUSPENDED'
            ) {
                throw new AuthorizationException;
            }
            $design = DB::table('blog_designs')
                ->where('blog_id', $blogId)
                ->lockForUpdate()
                ->first();
            abort_unless(
                $design?->draft_data && $design?->draft_template_id,
                422,
                'Chưa có bản nháp giao diện.',
            );
            $draft = json_decode($design->draft_data, true, 512, JSON_THROW_ON_ERROR);
            if (! hash_equals(JsonFingerprint::make($draft), $fingerprint)) {
                throw ValidationException::withMessages([
                    'fingerprint' => 'Bản nháp đã thay đổi ở cửa sổ khác.',
                ]);
            }
            $this->registry->validate($draft);
            DB::table('blog_designs')
                ->where('blog_id', $blogId)
                ->update([
                    'template_id' => $design->draft_template_id,
                    'published_data' => $design->draft_data,
                    'draft_template_id' => null,
                    'draft_data' => null,
                    'published_at' => now(),
                    'updated_by' => $actor->id,
                    'updated_at' => now(),
                ]);
            DB::table('blogs')
                ->where('id', $blogId)
                ->update([
                    'status' => 'PUBLISHED',
                    'published_at' => $blog->published_at ?: now(),
                    'updated_at' => now(),
                ]);
            $this->audit->record($actor->id, 'design.published', 'blog_design', $blogId, $blogId);
        });
    }
}
