<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteMediaAction
{
    public function execute(User $actor, int $blog, int $id): void
    {
        $asset = DB::transaction(function () use ($actor, $blog, $id) {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            $asset = DB::table('media_assets')->where(['id' => $id, 'blog_id' => $blog, 'scope' => 'BLOG'])->whereNull('deleted_at')->lockForUpdate()->firstOrFail();
            abort_unless(in_array($asset->status, ['READY', 'FAILED', 'DELETING']), 422);
            $used = false;
            foreach (['users' => ['avatar_media_id'], 'blog_settings' => ['logo_media_id', 'favicon_media_id', 'og_media_id'], 'templates' => ['preview_media_id'], 'posts' => ['cover_media_id', 'og_media_id'], 'pages' => ['og_media_id']] as $table => $columns) {
                foreach ($columns as $column) {
                    if (DB::table($table)->where($column, $id)->exists()) {
                        $used = true;
                    }
                }
            }
            $contains = function ($value) use (&$contains, $id): bool {
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

                    if (is_array($child) && $contains($child)) {
                        return true;
                    }
                }

                return false;
            };
            foreach (['templates' => ['template_data', 'draft_data'], 'blog_designs' => ['published_data', 'draft_data'], 'posts' => ['content_json', 'draft_data'], 'pages' => ['content_json', 'draft_data'], 'platform_settings' => ['config_json'], 'blog_settings' => ['social_links']] as $table => $columns) {
                foreach (DB::table($table)->select($columns)->cursor() as $row) {
                    foreach ($columns as $column) {
                        if ($row->$column && $contains(json_decode($row->$column, true))) {
                            $used = true;
                        }
                    }
                }
            }
            if ($used) {
                throw ValidationException::withMessages(['media' => 'Ảnh đang được tham chiếu trong nội dung, nháp, giao diện hoặc cài đặt. Hãy gỡ các tham chiếu trước khi xóa.']);
            }
            DB::table('media_assets')->where('id', $id)->update(['status' => 'DELETING', 'updated_at' => now()]);

            return $asset;
        });
        // Provider call is outside the database transaction. Retrying DELETING is idempotent.
        if ($asset->cloudinary_public_id && ! app(CloudinaryGateway::class)->destroy($asset->cloudinary_public_id)) {
            throw ValidationException::withMessages(['media' => 'Chưa xóa được tại nhà cung cấp. Có thể thử lại; ảnh đang bị chặn chèn mới.']);
        }
        DB::table('media_assets')->where(['id' => $id, 'blog_id' => $blog, 'status' => 'DELETING'])->update(['status' => 'DELETED', 'deleted_at' => now(), 'updated_at' => now()]);
    }
}
