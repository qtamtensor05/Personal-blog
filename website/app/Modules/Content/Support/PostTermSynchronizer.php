<?php

namespace App\Modules\Content\Support;

use Illuminate\Support\Facades\DB;

/**
 * Owns the category/tag relations of posts so that save and scheduled publish
 * share one definition of how terms are validated and replaced.
 */
final class PostTermSynchronizer
{
    /**
     * Term table => join table, join column and the key used inside draft payloads.
     */
    public const TERMS = [
        'categories' => ['join' => 'post_categories', 'column' => 'category_id', 'draftKey' => 'category_ids'],
        'tags' => ['join' => 'post_tags', 'column' => 'tag_id', 'draftKey' => 'tag_ids'],
    ];

    /**
     * Input rules shared by creating and renaming a category or tag, so a slug
     * accepted on create is never rejected on edit.
     */
    public const INPUT_RULES = [
        'name' => 'required|string|max:160',
        'slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
    ];

    /**
     * @param  array<int, mixed>  $termIds
     */
    public function allBelongToBlog(int $blogId, string $termTable, array $termIds): bool
    {
        return DB::table($termTable)
            ->where('blog_id', $blogId)
            ->whereIn('id', $termIds)
            ->count() === count(array_unique($termIds));
    }

    /**
     * @param  array<int, mixed>  $termIds
     */
    public function replace(int $blogId, int $postId, string $termTable, array $termIds): void
    {
        ['join' => $join, 'column' => $column] = self::TERMS[$termTable];

        DB::table($join)->where(['blog_id' => $blogId, 'post_id' => $postId])->delete();

        foreach (array_unique($termIds) as $termId) {
            DB::table($join)->insert([
                'blog_id' => $blogId,
                'post_id' => $postId,
                $column => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
