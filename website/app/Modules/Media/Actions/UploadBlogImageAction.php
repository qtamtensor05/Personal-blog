<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use RuntimeException;

final class UploadBlogImageAction
{
    public function __construct(private readonly CloudinaryGateway $cloudinary) {}

    public function execute(User $actor, int $blogId, UploadedFile $file, ?string $alt = null): int
    {
        $this->validateImage($file);
        $blog = DB::table('blogs')
            ->join('blog_settings', 'blog_settings.blog_id', '=', 'blogs.id')
            ->where('blogs.id', $blogId)
            ->where('blogs.owner_user_id', $actor->id)
            ->whereNull('blogs.deleted_at')
            ->where('blogs.status', '!=', 'SUSPENDED')
            ->first();
        if (! $blog || ! $actor->canAccessApplication()) {
            throw new AuthorizationException;
        }
        $id = DB::transaction(function () use ($actor, $blogId, $blog, $file, $alt): int {
            DB::table('blogs')->where('id', $blogId)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blogId, true);
            $used = (int) DB::table('media_assets')
                ->where('blog_id', $blogId)->whereIn('status', ['READY', 'UPLOADING', 'DELETING'])
                ->sum('bytes');
            abort_if(
                $used + $file->getSize() > (int) $blog->storage_quota_bytes,
                422,
                'Đã vượt quota lưu trữ của blog.',
            );

            return DB::table('media_assets')->insertGetId([
                'scope' => 'BLOG',
                'blog_id' => $blogId,
                'uploaded_by' => $actor->id,
                'cloud_name' => (string) config('services.cloudinary.cloud_name', 'pending'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'bytes' => $file->getSize(),
                'alt_text' => $alt,
                'status' => 'UPLOADING',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        $result = null;
        try {
            $result = $this->cloudinary->upload($file->getRealPath(), [
                'folder' => "blogs/{$blogId}",
                'resource_type' => 'image',
            ]);
            
            foreach (['asset_id', 'public_id', 'secure_url'] as $key) {
                if (
                    !is_string($result[$key] ?? null) ||
                    trim($result[$key]) === ''
                ) {
                    throw new RuntimeException(
                        'Cloudinary trả về dữ liệu ảnh không đầy đủ.'
                    );
                }
            }

            if (
                strlen($result['asset_id']) > 128 ||
                strlen($result['public_id']) > 255 ||
                strlen($result['secure_url']) > 2048 ||
                !filter_var($result['secure_url'], FILTER_VALIDATE_URL) ||
                parse_url($result['secure_url'], PHP_URL_SCHEME) !== 'https'
            ) {
                throw new RuntimeException(
                    'Thông tin ảnh từ Cloudinary không hợp lệ.'
                );
            }

            $updated = DB::table('media_assets')
                ->where(['id' => $id, 'blog_id' => $blogId, 'status' => 'UPLOADING'])
                ->update([
                    'cloud_name' => $result['cloud_name'] ?? (string) config('services.cloudinary.cloud_name', 'pending'),
                    'cloudinary_asset_id' => $result['asset_id'],
                    'cloudinary_public_id' => $result['public_id'],
                    'cloudinary_version' => $result['version'] ?? null,
                    'secure_url' => $result['secure_url'],
                    'format' => $result['format'] ?? null,
                    'width' => $result['width'] ?? null,
                    'height' => $result['height'] ?? null,
                    'bytes' => $result['bytes'] ?? $file->getSize(),
                    'status' => 'READY',
                    'updated_at' => now(),
                ]);
                if ($updated !== 1) {
                    throw new RuntimeException(
                        'Upload không còn ở trạng thái UPLOADING.'
                    );
                }
        } catch (Throwable $exception) {
            
            $publicId = is_array($result)
                && is_string($result['public_id'] ?? null)
                && strlen($result['public_id']) <= 255
                    ? $result['public_id']
                    : null;

            if ($publicId !== null) {
                try {
                    if ($this->cloudinary->destroy($publicId)) {
                        $publicId = null;
                    }
                } catch (Throwable $cleanupError) {
                    report($cleanupError);
                }
            }
            DB::table('media_assets')
                ->where('id', $id)
                ->update([
                    'status' => 'FAILED',
                    'cloudinary_public_id' => $publicId,
                    'updated_at' => now(),
                ]);

            throw $exception;
        }

        return $id;
    }

    private function validateImage(UploadedFile $file): void
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $size = $file->getSize();
        $dimensions = @getimagesize($file->getRealPath());
        $config = json_decode(DB::table('platform_settings')->where('id', 1)->value('config_json') ?? '{}', true);
        $limit = min(10 * 1024 * 1024, max(1024, (int) data_get($config, 'limits.maxImageUploadBytes', 10 * 1024 * 1024)));
        if (
            ! $file->isValid() ||
            ! in_array($file->getMimeType(), $allowedMimeTypes, true) ||
            ! is_int($size) ||
            $size < 1 ||
            $size > $limit ||
            $dimensions === false ||
            $dimensions[0] > 12000 ||
            $dimensions[1] > 12000
        ) {
            throw ValidationException::withMessages([
                'image' => 'Ảnh phải là JPEG, PNG, WebP hoặc GIF hợp lệ, tối đa 10 MB và 12000 px mỗi chiều.',
            ]);
        }
    }
}
