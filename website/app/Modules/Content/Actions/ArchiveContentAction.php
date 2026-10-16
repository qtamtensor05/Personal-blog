<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Content\Enums\ContentStatus;
use App\Modules\Content\Types\ContentTypeRegistry;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;

final class ArchiveContentAction
{
    public function __construct(
        private readonly ContentTypeRegistry $types,
        private readonly OwnerContext $owners,
        private readonly AuditRecorder $audit,
    ) {}

    public function execute(User $actor, int $blog, string $kind, int $id): void
    {
        $type = $this->types->for($kind);

        DB::transaction(function () use ($actor, $blog, $type, $id): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            $this->owners->resolve($actor, $blog, true);

            $row = DB::table($type->table())
                ->where(['id' => $id, 'blog_id' => $blog])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $type->assertCanArchive($blog, $row);

            DB::table($type->table())->where(['id' => $id, 'blog_id' => $blog])->update([
                'status' => ContentStatus::Archived->value,
                'scheduled_at' => null,
                'updated_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->audit->record($actor->id, $type->singular().'.archived', $type->singular(), $id, $blog);
        });
    }
}
