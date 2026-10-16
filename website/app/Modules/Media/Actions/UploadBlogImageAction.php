<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Media\Services\ProviderImageUploader;
use App\Modules\Platform\Contracts\PlatformConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UploadBlogImageAction
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const HARD_LIMIT_BYTES = 10 * 1024 * 1024;

    private const MAX_DIMENSION = 12000;

    public function __construct(
        private readonly OwnerContext $owners,
        private readonly PlatformConfig $config,
        private readonly ProviderImageUploader $uploader,
    ) {}

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

        $id = $this->reserve($actor, $blogId, $blog, $file, $alt);

        // The provider call stays outside the transaction (ADR-005).
        $this->uploader->upload($id, $file, "blogs/{$blogId}");

        return $id;
    }

    /**
     * Insert the UPLOADING row under the blog lock so quota checks serialize.
     */
    private function reserve(User $actor, int $blogId, object $blog, UploadedFile $file, ?string $alt): int
    {
        return DB::transaction(function () use ($actor, $blogId, $blog, $file, $alt): int {
            DB::table('blogs')->where('id', $blogId)->lockForUpdate()->first();
            $this->owners->resolve($actor, $blogId, true);

            $used = (int) DB::table('media_assets')
                ->where('blog_id', $blogId)
                ->whereIn('status', ['READY', 'UPLOADING', 'DELETING'])
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
    }

    private function validateImage(UploadedFile $file): void
    {
        $size = $file->getSize();
        $dimensions = @getimagesize($file->getRealPath());
        $limit = min(
            self::HARD_LIMIT_BYTES,
            max(1024, (int) $this->config->get('limits.maxImageUploadBytes', self::HARD_LIMIT_BYTES)),
        );

        if (
            ! $file->isValid()
            || ! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)
            || ! is_int($size)
            || $size < 1
            || $size > $limit
            || $dimensions === false
            || $dimensions[0] > self::MAX_DIMENSION
            || $dimensions[1] > self::MAX_DIMENSION
        ) {
            throw ValidationException::withMessages([
                'image' => 'Ảnh phải là JPEG, PNG, WebP hoặc GIF hợp lệ, tối đa 10 MB và 12000 px mỗi chiều.',
            ]);
        }
    }
}
