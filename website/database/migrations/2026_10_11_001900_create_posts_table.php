<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('posts')) {
            return;
        }

        Schema::create('posts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('title', 160);
            $table->string('slug', 160);
            $table->unsignedBigInteger('author_user_id');
            $table->text('excerpt')->nullable();
            $table->json('content_json');
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedBigInteger('og_media_id')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('robots', 80)->default('index,follow');
            $table->json('draft_data')->nullable();
            $table->enum('status', ['DRAFT', 'SCHEDULED', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'slug'], 'u_0');
            $table->unique(['blog_id', 'id'], 'u_1');
            $table->index(['blog_id', 'status', 'published_at'], 'i_0');
            $table->index(['status', 'scheduled_at'], 'i_1');
            $table->foreign('blog_id', 'fk_25_posts')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('author_user_id', 'fk_26_posts')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('updated_by', 'fk_27_posts')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('cover_media_id', 'fk_51_posts')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('og_media_id', 'fk_52_posts')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
