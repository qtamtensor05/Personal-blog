<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ViewPublicPost;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\PublicBlogView;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\DTOs\PostRecord;
use App\Application\Media\Contracts\MediaAssetRepository;

/**
 * A single PUBLISHED post. Blog visibility rules are reused from ViewPublicBlogService,
 * and only the live columns are rendered (draft_data never reaches visitors).
 */
final readonly class ViewPublicPostService
{
    public function __construct(
        private ViewPublicBlogService $blogs,
        private PostRepository $posts,
        private MediaAssetRepository $media,
    ) {}

    /**
     * @return array{page: PublicBlogView, post: PostRecord, media: array<int, array{url: string, width: ?int, height: ?int}>}
     */
    public function execute(string $blogSlug, string $postSlug, ?int $viewerId): array
    {
        $page = $this->blogs->execute($blogSlug, $viewerId);
        $post = $this->posts->findPublishedBySlug($page->blog->id, $postSlug);

        if ($post === null) {
            throw new BlogNotFound;
        }

        $ids = $post->content->mediaIds();

        if ($post->coverMediaId !== null) {
            $ids[] = $post->coverMediaId;
        }

        return [
            'page' => $page,
            'post' => $post,
            // Only READY media of this blog is resolved; anything else is not rendered.
            'media' => $this->media->readyAssetsForBlog($page->blog->id, array_values(array_unique($ids))),
        ];
    }
}
