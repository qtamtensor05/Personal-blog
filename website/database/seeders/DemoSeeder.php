<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->environment('production'), 403, 'DemoSeeder is disabled in production.');
        DB::transaction(function (): void {
            $users = collect([
                ['Nguyễn An', 'an.demo@example.test'],
                ['Trần Bình', 'binh.demo@example.test'],
            ])->map(
                fn ($item) => User::updateOrCreate(
                    ['email' => $item[1]],
                    [
                        'name' => $item[0],
                        'password' => 'DemoPassword!2026',
                        'status' => 'ACTIVE',
                        'email_verified_at' => now(),
                    ],
                ),
            );
            $templates = [
                ['Tạp chí tối giản', 'minimal-journal', 'text'],
                ['Nhật ký phiêu lưu', 'travel-lifestyle', 'hero'],
                ['Tòa soạn hiện đại', 'modern-magazine', 'post-list'],
            ];
            foreach ($templates as [$name, $slug, $type]) {
                $json = [
                    'schemaVersion' => 1,
                    'project' => [
                        'pages' => [
                            [
                                'id' => 'home',
                                'sections' => [
                                    ['id' => 'lead', 'type' => $type, 'content' => $name],
                                ],
                            ],
                        ],
                        'styles' => [],
                        'assets' => [],
                    ],
                    'themeDefaults' => ['accent' => '#23533b'],
                    'themeSchema' => [],
                    'sectionRules' => [
                        'allowedTypes' => [
                            'header',
                            'hero',
                            'text',
                            'image',
                            'post-list',
                            'footer',
                        ],
                    ],
                    'pageBindings' => [],
                    'navigation' => ['homepage' => 'home'],
                ];
                DB::table('templates')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => 'Mẫu demo có bố cục riêng biệt.',
                        'status' => 'PUBLISHED',
                        'created_by' => $users[0]->id,
                        'updated_by' => $users[0]->id,
                        'published_by' => $users[0]->id,
                        'version_number' => 1,
                        'template_data' => json_encode(
                            $json,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                        ),
                        'draft_data' => null,
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
            foreach (
                [
                    [$users[0], 'chuyen-cua-an', 'Chuyện của An'],
                    [$users[1], 'di-cung-binh', 'Đi cùng Bình'],
                ] as [$owner, $slug, $name]
            ) {
                $id =
                    DB::table('blogs')->where('slug', $slug)->value('id') ?:
                    DB::table('blogs')->insertGetId([
                        'owner_user_id' => $owner->id,
                        'name' => $name,
                        'slug' => $slug,
                        'description' => 'Blog minh họa cho môi trường local.',
                        'status' => 'PUBLISHED',
                        'visibility' => 'PUBLIC',
                        'discoverable' => true,
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                DB::table('blog_settings')->updateOrInsert(
                    ['blog_id' => $id],
                    [
                        'locale' => 'vi',
                        'timezone' => 'Asia/Ho_Chi_Minh',
                        'posts_per_page' => 10,
                        'storage_quota_bytes' => 104857600,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });
    }
}
