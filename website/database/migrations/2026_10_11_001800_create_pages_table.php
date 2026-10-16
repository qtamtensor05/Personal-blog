<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('title', 160);
            $table->string('slug', 160);
            $table->string('page_key', 80);
            $table->json('content_json');
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedBigInteger('og_media_id')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('robots', 80)->default('index,follow');
            $table->json('draft_data')->nullable();
            $table
                ->enum('status', ['DRAFT', 'SCHEDULED', 'PUBLISHED', 'ARCHIVED'])
                ->default('DRAFT');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'slug'], 'u_0');
            $table->unique(['blog_id', 'page_key'], 'u_1');
            $table->unique(['blog_id', 'id'], 'u_2');
            $table
                ->foreign('blog_id', 'fk_22_pages')
                ->references('id')
                ->on('blogs')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('created_by', 'fk_23_pages')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('updated_by', 'fk_24_pages')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('og_media_id', 'fk_54_pages')
                ->references('id')
                ->on('media_assets')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
