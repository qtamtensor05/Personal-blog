<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Design\DesignDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403, 'DemoSeeder is only available in local/testing.');
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
                ['Nhật ký', 'minimal-journal', 'journal'],
                ['Hành trình', 'travel-lifestyle', 'journey'],
                ['Tạp chí', 'modern-magazine', 'magazine'],
            ];
            foreach ($templates as [$name, $slug, $type]) {
                $json = [
                    'schemaVersion' => 1,
                    'project' => [
                        'pages' => [
                            [
                                'id' => 'home',
                                'sections' => [
                                    ['id' => 'lead', 'type' => 'hero', 'content' => match ($type) {
                                        'journey' => 'Những hành trình làm nên tôi', 'magazine' => 'Những góc nhìn về thế giới hiện đại', default => 'Những điều bình dị trong cuộc sống'
                                    }, 'description' => 'Ghi lại trải nghiệm và những câu chuyện đáng nhớ.'],
                                    ['id' => 'posts', 'type' => 'post-list'],
                                ],
                            ],
                        ],
                        'styles' => [],
                        'assets' => [],
                    ],
                    'themeDefaults' => ['accent' => '#147d73', 'layout' => $type],
                    'theme' => ['accent' => '#147d73', 'layout' => $type],
                    'customizationRules' => ['fields' => ['accent', 'heading', 'description', 'layout', 'footer']],
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
                $categoryId = DB::table('template_categories')->where(['kind' => 'TOPIC', 'slug' => $type])->value('id') ?: DB::table('template_categories')->insertGetId(['kind' => 'TOPIC', 'slug' => $type, 'name' => match ($type) {
                    'journey' => 'Du lịch','magazine' => 'Tạp chí',default => 'Cá nhân'
                }, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('template_category_assignments')->updateOrInsert(['template_id' => DB::table('templates')->where('slug', $slug)->value('id'), 'category_id' => $categoryId], ['created_at' => now(), 'updated_at' => now()]);
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
                $template = DB::table('templates')->where('slug', $owner->id === $users[0]->id ? 'minimal-journal' : 'travel-lifestyle')->first();
                DB::table('blog_designs')->updateOrInsert(['blog_id' => $id], ['template_id' => $template->id, 'published_data' => json_encode(DesignDocument::snapshot($template), JSON_THROW_ON_ERROR), 'draft_template_id' => null, 'draft_data' => null, 'published_at' => now(), 'updated_by' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('posts')->updateOrInsert(['blog_id' => $id, 'slug' => 'cau-chuyen-dau-tien'], ['title' => 'Câu chuyện đầu tiên', 'author_user_id' => $owner->id, 'updated_by' => $owner->id, 'excerpt' => 'Một khởi đầu mới cho những điều đáng nhớ.', 'content_json' => json_encode(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Chào mừng bạn đến với blog demo. Nội dung này được tạo bởi DemoSeeder trong môi trường phát triển.']]]]], JSON_THROW_ON_ERROR), 'status' => 'PUBLISHED', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}
