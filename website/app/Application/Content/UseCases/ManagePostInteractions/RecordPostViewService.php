<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Content\Contracts\PostInteractionRepository;

final readonly class RecordPostViewService implements RecordPostViewUseCase
{
    public function __construct(private PostInteractionRepository $interactions) {}

    /** One permitted real-post read per account or guest session for the post's lifetime; owner visits are excluded. */
    public function execute(int $postId, int $ownerId, ?int $readerId, string $sessionId): void
    {
        if ($readerId === $ownerId) {
            return;
        }

        $key = hash('sha256', $readerId !== null ? 'user:'.$readerId : 'session:'.$sessionId);
        $this->interactions->recordView($postId, $key, now('UTC')->toDateString());
    }
}
