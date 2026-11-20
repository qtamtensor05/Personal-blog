<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\GenerateImageContent;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\ImageContentGenerator;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\DTOs\AnalysisImage;
use App\Application\Content\DTOs\ImageWritingInput;
use App\Application\Content\DTOs\PostWritingSuggestion;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use DomainException;

final readonly class GenerateImageContentService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private PostRepository $posts,
        private MediaAssetRepository $media,
        private ImageContentGenerator $generator,
    ) {}

    public function execute(
        int $actorId,
        int $blogId,
        int $postId,
        int $mediaId,
        string $title,
        string $context,
        string $instructions,
        string $locale,
        array $fields = PostWritingSuggestion::FIELDS,
        ?AnalysisImage $analysisImage = null,
    ): PostWritingSuggestion {
        $blog = $this->guard->ensure($actorId, $blogId);
        if ($blog->deletedAt !== null || $this->posts->find($blogId, $postId) === null) {
            throw new BlogNotFound;
        }
        if ($blog->status === BlogStatus::Suspended) {
            throw new DomainException('This blog is suspended and cannot be changed.');
        }

        $image = $this->media->find($mediaId);
        if ($image === null || $image->scope !== MediaScope::Blog || $image->blogId !== $blogId || $image->status !== MediaStatus::Ready) {
            throw new DomainException('Choose an available image from this blog.');
        }
        if ($image->resourceType !== 'image' || ! in_array($image->mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new DomainException('AI writing supports JPG, PNG and WEBP images.');
        }
        if (! in_array($locale, ['vi', 'en'], true) || mb_strlen($title) > 160 || mb_strlen($context) > 4000 || mb_strlen($instructions) > 1000) {
            throw new DomainException('The AI writing request is invalid.');
        }

        if ($fields === [] || ! array_is_list($fields) || array_diff($fields, PostWritingSuggestion::FIELDS) !== []) {
            throw new DomainException('The AI writing request is invalid.');
        }

        return $this->generator->generate(new ImageWritingInput($image, $title, $context, $instructions, $locale, array_values(array_unique($fields)), $analysisImage));
    }
}
