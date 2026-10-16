<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Media\MediaReferenceFinder;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteMediaAction
{
    public function __construct(
        private readonly OwnerContext $owners,
        private readonly MediaReferenceFinder $references,
        private readonly CloudinaryGateway $cloudinary,
    ) {}

    public function execute(User $actor, int $blog, int $id): void
    {
        $asset = $this->markDeleting($actor, $blog, $id);

        // Provider call is outside the database transaction. Retrying DELETING is idempotent.
        if ($asset->cloudinary_public_id && ! $this->cloudinary->destroy($asset->cloudinary_public_id)) {
            throw ValidationException::withMessages([
                'media' => 'Chưa xóa được tại nhà cung cấp. Có thể thử lại; ảnh đang bị chặn chèn mới.',
            ]);
        }

        DB::table('media_assets')
            ->where(['id' => $id, 'blog_id' => $blog, 'status' => 'DELETING'])
            ->update(['status' => 'DELETED', 'deleted_at' => now(), 'updated_at' => now()]);
    }

    private function markDeleting(User $actor, int $blog, int $id): object
    {
        return DB::transaction(function () use ($actor, $blog, $id): object {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            $this->owners->resolve($actor, $blog, true);

            $asset = DB::table('media_assets')
                ->where(['id' => $id, 'blog_id' => $blog, 'scope' => 'BLOG'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(in_array($asset->status, ['READY', 'FAILED', 'DELETING']), 422);

            if ($this->references->isReferencedByColumn($id) || $this->references->isReferencedByDocument($id)) {
                throw ValidationException::withMessages([
                    'media' => 'Ảnh đang được tham chiếu trong nội dung, nháp, giao diện hoặc cài đặt. Hãy gỡ các tham chiếu trước khi xóa.',
                ]);
            }

            DB::table('media_assets')->where('id', $id)->update(['status' => 'DELETING', 'updated_at' => now()]);

            return $asset;
        });
    }
}
