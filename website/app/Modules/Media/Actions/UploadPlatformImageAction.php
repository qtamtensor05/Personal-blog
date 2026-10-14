<?php
namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class UploadPlatformImageAction
{
    public function __construct(
        private readonly CloudinaryGateway $cloudinary
    ) {}

    public function execute(
        User $actor,
        UploadedFile $file,
        ?string $alt = null
    ): int {
        abort_unless(
            $actor->canAccessApplication() &&
            $actor->hasPlatformPermission('platform.media.manage'),
            403
        );

        Validator::make(
            ['image' => $file, 'alt' => $alt],
            [
                'image' => 'required|image|mimes:jpeg,png,webp,gif|max:10240',
                'alt' => 'nullable|string|max:500',
            ]
        )->validate();

        $id = DB::table('media_assets')->insertGetId([
            'scope' => 'PLATFORM',
            'blog_id' => null,
            'uploaded_by' => $actor->id,
            'cloud_name' => (string) config(
                'services.cloudinary.cloud_name',
                'pending'
            ),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'bytes' => $file->getSize(),
            'status' => 'UPLOADING',
            'alt_text' => $alt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = null;

        try {
            $result = $this->cloudinary->upload(
                $file->getRealPath(),
                [
                    'folder' => 'platform',
                    'resource_type' => 'image',
                ]
            );

            foreach (
                ['asset_id', 'public_id', 'secure_url'] as $key
            ) {
                if (
                    !is_string($result[$key] ?? null) ||
                    trim($result[$key]) === ''
                ) {
                    throw new RuntimeException(
                        'Cloudinary trả về thông tin ảnh không đầy đủ.'
                    );
                }
            }

            if (
                strlen($result['asset_id']) > 128 ||
                strlen($result['public_id']) > 255 ||
                strlen($result['secure_url']) > 2048 ||
                !filter_var(
                    $result['secure_url'],
                    FILTER_VALIDATE_URL
                ) ||
                parse_url(
                    $result['secure_url'],
                    PHP_URL_SCHEME
                ) !== 'https'
            ) {
                throw new RuntimeException(
                    'URL hoặc mã tài nguyên từ Cloudinary không hợp lệ.'
                );
            }

            $updated = DB::table('media_assets')
                ->where('id', $id)
                ->where('status', 'UPLOADING')
                ->update([
                    'cloud_name' =>
                        $result['cloud_name']
                        ?? (string) config('services.cloudinary.cloud_name'),
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

            return $id;

        } catch (Throwable $e) {
            $publicId = is_array($result) &&
                is_string($result['public_id'] ?? null) &&
                strlen($result['public_id']) <= 255
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

            report($e);

            throw ValidationException::withMessages([
                'image' =>
                    'Không tải được ảnh lên Cloudinary. Vui lòng thử lại.'
            ]);
        }
    }
}
