<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Media\Exceptions\MediaAccessDenied;
use App\Application\Media\Exceptions\MediaInUse;
use App\Application\Media\Exceptions\MediaQuotaExceeded;
use App\Application\Media\Exceptions\MediaStorageFailed;
use App\Application\Media\UseCases\DeleteMedia\DeleteMediaUseCase;
use App\Application\Media\UseCases\UploadMedia\UploadMediaUseCase;
use App\Domain\Media\Enums\MediaScope;
use App\Presentation\Web\Requests\UploadMediaRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * JSON endpoints used by the editors (session + CSRF, no separate API auth).
 */
final class MediaController extends Controller
{
    public function store(UploadMediaRequest $request, UploadMediaUseCase $upload): JsonResponse
    {
        $file = $request->file('file');

        try {
            $asset = $upload->execute(
                actorId: (int) $request->user()->id,
                scope: MediaScope::from($request->validated('scope')),
                blogId: $request->validated('blog_id') !== null ? (int) $request->validated('blog_id') : null,
                localPath: $file->getRealPath(),
                originalName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                sizeBytes: (int) $file->getSize(),
                altText: $request->validated('alt_text'),
            );
        } catch (MediaAccessDenied $exception) {
            return $this->error($exception->getMessage(), 403);
        } catch (MediaQuotaExceeded $exception) {
            return $this->error($exception->getMessage(), 422);
        } catch (MediaStorageFailed $exception) {
            Log::error('Media upload failed.', ['exception' => $exception]);

            return $this->error('The image could not be uploaded. Please try again.', 502);
        }

        return new JsonResponse(['data' => $this->present($asset)], 201);
    }

    public function destroy(Request $request, int $media, DeleteMediaUseCase $delete): JsonResponse
    {
        try {
            $delete->execute((int) $request->user()->id, $media);
        } catch (MediaAccessDenied) {
            return $this->error('Media not found.', 404);
        } catch (MediaInUse $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (MediaStorageFailed $exception) {
            Log::error('Media delete failed.', ['exception' => $exception]);

            return $this->error('The image could not be deleted. Please try again.', 502);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 409);
        }

        return new JsonResponse(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(MediaAssetRecord $asset): array
    {
        return [
            'id' => $asset->id,
            'scope' => $asset->scope->value,
            'blog_id' => $asset->blogId,
            'url' => $asset->secureUrl,
            'width' => $asset->width,
            'height' => $asset->height,
            'bytes' => $asset->bytes,
            'alt_text' => $asset->altText,
            'status' => $asset->status->value,
        ];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}
