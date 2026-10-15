<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeletePlatformImageAction
{
    public function __construct(
        private readonly CloudinaryGateway $cloudinary
    ) {}

    public function execute(User $actor, int $id): void
    {
        abort_unless(
            $actor->canAccessApplication() &&
            $actor->hasPlatformPermission('platform.media.manage'),
            403
        );

        $asset = DB::transaction(function () use ($id) {
            $row = DB::table('media_assets')
                ->where(['id' => $id, 'scope' => 'PLATFORM'])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                in_array($row->status, ['READY', 'FAILED', 'DELETING'], true),
                422
            );

            $references = [
                'users' => ['avatar_media_id'],
                'blog_settings' => [
                    'logo_media_id',
                    'favicon_media_id',
                    'og_media_id',
                ],
                'templates' => ['preview_media_id'],
                'posts' => ['cover_media_id', 'og_media_id'],
                'pages' => ['og_media_id'],
            ];

            foreach ($references as $table => $columns) {
                foreach ($columns as $column) {
                    if (DB::table($table)->where($column, $id)->exists()) {
                        throw ValidationException::withMessages([
                            'media' => 'Ảnh đang được hệ thống sử dụng.',
                        ]);
                    }
                }
            }

            $contains = function (mixed $value) use (
                &$contains, $id
            ): bool {
                if (! is_array($value)) {
                    return false;
                }

                foreach ($value as $key => $child) {
                    if (
                        in_array($key, [
                            'mediaId',
                            'media_id',
                            'asset_id',
                            'logoMediaId',
                            'previewMediaId',
                            'og_media_id',
                            'cover_media_id',
                        ], true)
                        && (is_int($child) || is_string($child))
                        && (string) $child === (string) $id
                    ) {
                        return true;
                    }
                    if ($contains($child)) {
                        return true;
                    }
                }

                return false;
            };

            $jsonReferences = [
                'templates' => ['template_data', 'draft_data'],
                'blog_designs' => ['published_data', 'draft_data'],
                'posts' => ['content_json', 'draft_data'],
                'pages' => ['content_json', 'draft_data'],
                'platform_settings' => ['config_json'],
                'blog_settings' => ['social_links'],
            ];

            foreach ($jsonReferences as $table => $columns) {
                foreach (
                    DB::table($table)->select($columns)->cursor() as $item
                ) {
                    foreach ($columns as $column) {
                        if (
                            $item->$column &&
                            $contains(json_decode($item->$column, true))
                        ) {
                            throw ValidationException::withMessages([
                                'media' => 'Ảnh còn được dùng trong nội dung hoặc giao diện.',
                            ]);
                        }
                    }
                }
            }

            DB::table('media_assets')
                ->where('id', $id)
                ->update([
                    'status' => 'DELETING',
                    'updated_at' => now(),
                ]);

            return $row;
        });

        if (
            $asset->cloudinary_public_id &&
            ! $this->cloudinary->destroy(
                $asset->cloudinary_public_id
            )
        ) {
            throw ValidationException::withMessages([
                'media' => 'Cloudinary chưa xóa được ảnh. Có thể thử lại.',
            ]);
        }

        DB::table('media_assets')
            ->where([
                'id' => $id,
                'scope' => 'PLATFORM',
                'status' => 'DELETING',
            ])
            ->update([
                'status' => 'DELETED',
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
