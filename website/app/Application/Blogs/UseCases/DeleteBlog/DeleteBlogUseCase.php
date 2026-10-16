<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\DeleteBlog;

interface DeleteBlogUseCase
{
    public function execute(int $actorId, int $blogId): void;
}
