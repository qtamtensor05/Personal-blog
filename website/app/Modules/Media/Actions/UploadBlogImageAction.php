<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            ->first();
        if (! $blog || $actor->status !== 'ACTIVE' || ! $actor->hasVerifiedEmail()) {
            throw new AuthorizationException;
        }
        $used = (int) DB::table('media_assets')
            ->where(['blog_id' => $blogId, 'status' => 'READY'])
            ->sum('bytes');
        abort_if(
            $used + $file->getSize() > (int) $blog->storage_quota_bytes,
            422,
            'Đã vượt quota lưu trữ của blog.',
        );
        $id = DB::table('media_assets')->insertGetId([
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
        try {
            $result = $this->cloudinary->upload($file->getRealPath(), [
                'folder' => "blogs/{$blogId}",
                'resource_type' => 'image',
            ]);
            DB::table('media_assets')
                ->where(['id' => $id, 'blog_id' => $blogId, 'status' => 'UPLOADING'])
                ->update([
                    'cloud_name' => $result['cloud_name'],
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
        } catch (Throwable $exception) {
            DB::table('media_assets')
                ->where('id', $id)
                ->update(['status' => 'FAILED', 'updated_at' => now()]);
            throw $exception;
        }

        return $id;
    }

    private function validateImage(UploadedFile $file): void
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $size = $file->getSize();
        $dimensions = @getimagesize($file->getRealPath());
        if (
            ! $file->isValid() ||
            ! in_array($file->getMimeType(), $allowedMimeTypes, true) ||
            ! is_int($size) ||
            $size < 1 ||
            $size > 10 * 1024 * 1024 ||
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
