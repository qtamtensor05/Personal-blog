<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\CloudinaryUploadResult;
use App\Shared\Contracts\CloudinaryGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Moves a reserved (UPLOADING) media asset to the provider and finalises it.
 * Shared by blog and platform uploads; each caller owns authorisation,
 * validation and how the reservation row is created.
 */
final class ProviderImageUploader
{
    public function __construct(private readonly CloudinaryGateway $cloudinary) {}

    /**
     * Marks the asset READY on success. On any failure the provider copy is
     * removed when possible, the asset is marked FAILED and the original
     * exception is rethrown for the caller to translate.
     *
     * @throws Throwable
     */
    public function upload(int $assetId, UploadedFile $file, string $folder): void
    {
        $payload = null;

        try {
            $payload = $this->cloudinary->upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'image',
            ]);

            $result = CloudinaryUploadResult::fromPayload($payload);

            $updated = DB::table('media_assets')
                ->where(['id' => $assetId, 'status' => 'UPLOADING'])
                ->update($result->toReadyColumns(
                    (string) config('services.cloudinary.cloud_name', 'pending'),
                    $file->getSize() ?: null,
                ));

            if ($updated !== 1) {
                throw new RuntimeException('Upload không còn ở trạng thái UPLOADING.');
            }
        } catch (Throwable $exception) {
            $this->markFailed($assetId, CloudinaryUploadResult::salvagePublicId(is_array($payload) ? $payload : null));

            throw $exception;
        }
    }

    private function markFailed(int $assetId, ?string $publicId): void
    {
        if ($publicId !== null) {
            try {
                // A destroyed provider copy leaves nothing to reconcile later.
                if ($this->cloudinary->destroy($publicId)) {
                    $publicId = null;
                }
            } catch (Throwable $cleanupError) {
                report($cleanupError);
            }
        }

        DB::table('media_assets')->where('id', $assetId)->update([
            'status' => 'FAILED',
            'cloudinary_public_id' => $publicId,
            'updated_at' => now(),
        ]);
    }
}
