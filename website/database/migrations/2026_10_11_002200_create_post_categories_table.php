<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_categories')) {
            return;
        }

        Schema::create('post_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('category_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['post_id', 'category_id'], 'u_0');
            $table->foreign(['blog_id', 'post_id'], 'fk_31_post_categories')->references(['blog_id', 'id'])->on('posts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['blog_id', 'category_id'], 'fk_32_post_categories')->references(['blog_id', 'id'])->on('categories')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
