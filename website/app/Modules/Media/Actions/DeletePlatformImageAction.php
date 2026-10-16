<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Media\MediaReferenceFinder;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeletePlatformImageAction
{
    public function __construct(
        private readonly CloudinaryGateway $cloudinary,
        private readonly MediaReferenceFinder $references,
    ) {}

    public function execute(User $actor, int $id): void
    {
        abort_unless(
            $actor->canAccessApplication()
            && $actor->hasPlatformPermission('platform.media.manage'),
            403
        );

        $asset = $this->markDeleting($id);

        if ($asset->cloudinary_public_id && ! $this->cloudinary->destroy($asset->cloudinary_public_id)) {
            throw ValidationException::withMessages(['media' => 'Cloudinary chưa xóa được ảnh. Có thể thử lại.']);
        }

        DB::table('media_assets')
            ->where(['id' => $id, 'scope' => 'PLATFORM', 'status' => 'DELETING'])
            ->update(['status' => 'DELETED', 'deleted_at' => now(), 'updated_at' => now()]);
    }

    private function markDeleting(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $row = DB::table('media_assets')
                ->where(['id' => $id, 'scope' => 'PLATFORM'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(in_array($row->status, ['READY', 'FAILED', 'DELETING'], true), 422);

            if ($this->references->isReferencedByColumn($id)) {
                throw ValidationException::withMessages(['media' => 'Ảnh đang được hệ thống sử dụng.']);
            }

            if ($this->references->isReferencedByDocument($id)) {
                throw ValidationException::withMessages(['media' => 'Ảnh còn được dùng trong nội dung hoặc giao diện.']);
            }

            DB::table('media_assets')->where('id', $id)->update(['status' => 'DELETING', 'updated_at' => now()]);

            return $row;
        });
    }
}
