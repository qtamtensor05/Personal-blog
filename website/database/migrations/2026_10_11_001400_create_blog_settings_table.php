<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_settings')) {
            return;
        }

        Schema::create('blog_settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('blog_id')->primary();
            $table->string('locale', 20)->default('vi');
            $table->string('timezone', 80)->default('Asia/Ho_Chi_Minh');
            $table->unsignedBigInteger('logo_media_id')->nullable();
            $table->unsignedBigInteger('favicon_media_id')->nullable();
            $table->unsignedInteger('posts_per_page')->default(10);
            $table->json('social_links')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedBigInteger('og_media_id')->nullable();
            $table->unsignedBigInteger('storage_quota_bytes')->default(104857600);
            $table->unsignedInteger('post_limit')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->foreign('blog_id', 'fk_15_blog_settings')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('logo_media_id', 'fk_38_blog_settings')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('favicon_media_id', 'fk_39_blog_settings')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('og_media_id', 'fk_40_blog_settings')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement('ALTER TABLE `blog_settings` ADD CONSTRAINT `ck_blog_settings_0` CHECK (`posts_per_page` BETWEEN 1 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_settings');
    }
};
