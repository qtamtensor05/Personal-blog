<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Templates\TemplateStarters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class LifestyleEditorialTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('templates')->where('slug', 'lifestyle-editorial')->exists()) {
            return;
        }

        $adminId = DB::table('users')
            ->join('user_platform_roles', 'users.id', '=', 'user_platform_roles.user_id')
            ->join('platform_roles', 'platform_roles.id', '=', 'user_platform_roles.role_id')
            ->where('platform_roles.code', 'SUPER_ADMIN')
            ->orderBy('users.id')
            ->value('users.id');

        if ($adminId === null) {
            $this->command?->warn('Skipping Lifestyle Editorial template: no SUPER_ADMIN user exists.');

            return;
        }

        $now = now();
        $data = json_encode(TemplateStarters::lifestyleEditorial(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        DB::table('templates')->insert([
            'name' => 'Lifestyle Editorial',
            'slug' => 'lifestyle-editorial',
            'description' => 'Mẫu blog phong cách tạp chí sống chậm với hero toàn màn hình, bài viết dạng lưới, giới thiệu, gallery, sidebar và footer cố định.',
            'status' => 'PUBLISHED',
            'preview_media_id' => null,
            'demo_url' => null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'published_by' => $adminId,
            'published_at' => $now,
            'version_number' => 1,
            'template_data' => $data,
            'draft_data' => $data,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
