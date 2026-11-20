<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\DTOs\AnalysisImage;
use App\Application\Content\DTOs\PostWritingSuggestion;
use App\Application\Content\Exceptions\ImageWritingUnavailable;
use App\Application\Content\UseCases\GenerateImageContent\GenerateImageContentService;
use App\Presentation\Web\Requests\GenerateImageContentRequest;
use DomainException;
use Illuminate\Http\JsonResponse;

final class ImageWritingController
{
    public function __invoke(int $blog, int $post, GenerateImageContentRequest $request, GenerateImageContentService $service): JsonResponse
    {
        $data = $request->validated();
        try {
            $suggestion = $service->execute(
                (int) $request->user()->id,
                $blog,
                $post,
                (int) $data['media_id'],
                $data['title'] ?? '',
                $data['context'] ?? '',
                $data['instructions'] ?? '',
                $data['locale'],
                $data['fields'] ?? PostWritingSuggestion::FIELDS,
                $request->hasFile('analysis_image') ? new AnalysisImage($request->file('analysis_image')->get(), $request->file('analysis_image')->getMimeType()) : null,
            );
        } catch (BlogNotFound) {
            return new JsonResponse(['message' => __('Not found.')], 404);
        } catch (DomainException $exception) {
            return new JsonResponse(['message' => __($exception->getMessage())], 422);
        } catch (ImageWritingUnavailable $exception) {
            return new JsonResponse(['message' => __($exception->getMessage())], 503);
        }

        return new JsonResponse(['data' => $suggestion->toArray()], headers: ['Cache-Control' => 'no-store']);
    }
}
