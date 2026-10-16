<?php

namespace App\Modules\Content\Support;

use App\Modules\Content\Enums\SaveIntent;
use App\Shared\Contracts\RichTextRenderer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Turns raw editor input into validated, normalised data. Contains no
 * persistence so it can be reasoned about and tested on its own.
 */
final class ContentInputPreparer
{
    private const ROBOTS = [
        'index,follow',
        'index,nofollow',
        'noindex,follow',
        'noindex,nofollow',
    ];

    public function __construct(
        private readonly PublicationTime $publicationTime,
        private readonly RichTextRenderer $richText,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: SaveIntent}
     */
    public function prepare(int $blogId, bool $isNew, array $input, string $intent): array
    {
        $data = Validator::make($input, $this->rules())->validate();
        $data = $this->normaliseRobots($data, $isNew);

        $intent = SaveIntent::fromRequest($intent);

        if ($intent === SaveIntent::Schedule) {
            $data['scheduled_at'] = $this->resolveScheduledAt($blogId, $data['scheduled_at'] ?? null);
        }

        $this->assertRenderable($data['content_json'], $blogId);

        return [$data, $intent];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => 'required|string|max:160',
            'slug' => 'required|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:160',
            'excerpt' => 'nullable|string|max:2000',
            'content_json' => 'required|array',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'cover_media_id' => 'nullable|integer',
            'og_media_id' => 'nullable|integer',
            'canonical_url' => 'nullable|url:http,https|max:2048',
            'robots' => ['nullable', Rule::in(self::ROBOTS)],
            'scheduled_at' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'integer',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer',
        ];
    }

    /**
     * New content defaults to indexable; an existing row keeps its robots value
     * unless the editor sends a new one.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliseRobots(array $data, bool $isNew): array
    {
        if ($isNew) {
            $data['robots'] = $data['robots'] ?? self::ROBOTS[0];
        } elseif (array_key_exists('robots', $data) && $data['robots'] === null) {
            unset($data['robots']);
        }

        return $data;
    }

    private function resolveScheduledAt(int $blogId, mixed $scheduledAt): string
    {
        if (empty($scheduledAt)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Chọn thời gian xuất bản trong tương lai theo múi giờ blog.',
            ]);
        }

        return $this->publicationTime->toUtc($blogId, (string) $scheduledAt);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function assertRenderable(array $document, int $blogId): void
    {
        try {
            $this->richText->render($document, $blogId);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['content_json' => $e->getMessage()]);
        }
    }
}
