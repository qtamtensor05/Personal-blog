<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;

final class ArchiveContentAction
{
    public function execute(User $actor, int $blog, string $kind, int $id): void
    {
        abort_unless(in_array($kind, ['posts', 'pages']), 404);
        DB::transaction(function () use ($actor, $blog, $kind, $id): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            $row = DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->whereNull('deleted_at')->lockForUpdate()->firstOrFail();
            if ($kind === 'pages') {
                $design = DB::table('blog_designs')->where('blog_id', $blog)->first();
                foreach (['published_data', 'draft_data'] as $key) {
                    abort_if(in_array($row->page_key, data_get(json_decode($design?->$key ?? '{}', true), 'navigation.pageKeys', []), true), 422, 'Gỡ trang khỏi menu nháp và live trước khi lưu trữ.');
                }
            }
            DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update(['status' => 'ARCHIVED', 'scheduled_at' => null, 'updated_at' => now(), 'updated_by' => $actor->id]);
            app(AuditRecorder::class)->record($actor->id, rtrim($kind, 's').'.archived', rtrim($kind,'s'), $id, $blog);
        });
    }
}
