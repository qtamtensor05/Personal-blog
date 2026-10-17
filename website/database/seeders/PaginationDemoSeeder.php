<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PaginationDemoSeeder extends Seeder
{
    private const BLOG_SLUG = 'pagination-demo';

    public function run(): void
    {
        $hasRealPublicPosts = DB::table('posts as p')
            ->join('blogs as b', 'b.id', '=', 'p.blog_id')
            ->where('p.status', 'PUBLISHED')
            ->whereNull('p.deleted_at')
            ->where('b.status', 'PUBLISHED')
            ->where('b.visibility', 'PUBLIC')
            ->where('b.discoverable', true)
            ->whereNull('b.deleted_at')
            ->where('b.slug', '!=', self::BLOG_SLUG)
            ->exists();

        if ($hasRealPublicPosts) {
            $this->command?->warn('Skipped pagination demo: real public posts already exist.');

            return;
        }

        DB::transaction(function (): void {
            $now = now();
            $email = 'pagination-demo@example.test';

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => 'Pagination Demo',
                    'password' => Hash::make(Str::random(40)),
                    'status' => 'ACTIVE',
                    'email_verified_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $userId = (int) DB::table('users')->where('email', $email)->value('id');

            DB::table('blogs')->updateOrInsert(
                ['slug' => self::BLOG_SLUG],
                [
                    'owner_user_id' => $userId,
                    'name' => 'Pagination Demo Blog',
                    'description' => 'Temporary development content for pagination verification.',
                    'status' => 'PUBLISHED',
                    'visibility' => 'PUBLIC',
                    'discoverable' => true,
                    'published_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $blogId = (int) DB::table('blogs')->where('slug', self::BLOG_SLUG)->value('id');

            foreach (range(1, 19) as $number) {
                DB::table('posts')->updateOrInsert(
                    ['blog_id' => $blogId, 'slug' => sprintf('pagination-demo-post-%02d', $number)],
                    [
                        'title' => sprintf('Pagination demo post %02d', $number),
                        'author_user_id' => $userId,
                        'excerpt' => sprintf('Demo article %02d used to verify page navigation and ordering.', $number),
                        'content_json' => json_encode([
                            'type' => 'doc',
                            'content' => [[
                                'type' => 'paragraph',
                                'content' => [[
                                    'type' => 'text',
                                    'text' => sprintf('Pagination demo content for article %02d.', $number),
                                ]],
                            ]],
                        ], JSON_THROW_ON_ERROR),
                        'status' => 'PUBLISHED',
                        'published_at' => $now->copy()->subMinutes($number),
                        'is_featured' => $number === 1,
                        'updated_by' => $userId,
                        'updated_at' => $now,
                    ],
                );
            }
        });

        $this->command?->info('Created 19 pagination demo posts (3 pages at 9 posts per page).');
    }
}
