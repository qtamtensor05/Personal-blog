<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Media\Services\ProviderImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UploadPlatformImageAction
{
    public function __construct(private readonly ProviderImageUploader $uploader) {}

    public function execute(User $actor, UploadedFile $file, ?string $alt = null): int
    {
        abort_unless(
            $actor->canAccessApplication()
            && $actor->hasPlatformPermission('platform.media.manage'),
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
            'cloud_name' => (string) config('services.cloudinary.cloud_name', 'pending'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'bytes' => $file->getSize(),
            'status' => 'UPLOADING',
            'alt_text' => $alt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->uploader->upload($id, $file, 'platform');
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'image' => 'Không tải được ảnh lên Cloudinary. Vui lòng thử lại.',
            ]);
        }

        return $id;
    }
}
