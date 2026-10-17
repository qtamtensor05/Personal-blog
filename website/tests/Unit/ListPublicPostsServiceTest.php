<?php

use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\DTOs\PublicPostPage;
use App\Application\Content\UseCases\ListPublicPosts\ListPublicPostsService;

it('normalizes pagination input before querying the public catalog', function (): void {
    $catalog = new class implements PublicPostCatalog
    {
        public ?string $search = null;

        public int $page = 0;

        public int $perPage = 0;

        public function paginate(?string $search, int $page, int $perPage): PublicPostPage
        {
            $this->search = $search;
            $this->page = $page;
            $this->perPage = $perPage;

            return new PublicPostPage([], 0, $page, $perPage);
        }
    };

    $result = (new ListPublicPostsService($catalog))->execute('  Laravel  ', -5);

    expect($catalog->search)->toBe('Laravel')
        ->and($catalog->page)->toBe(1)
        ->and($catalog->perPage)->toBe(9)
        ->and($result->currentPage)->toBe(1);
});

it('calculates the final page from the real total', function (): void {
    $page = new PublicPostPage([], 19, 2, 9);

    expect($page->lastPage())->toBe(3);
});
