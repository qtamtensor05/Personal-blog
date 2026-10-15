<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_tags')) {
            return;
        }

        Schema::create('post_tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('tag_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['post_id', 'tag_id'], 'u_0');
            $table->foreign(['blog_id', 'post_id'], 'fk_33_post_tags')->references(['blog_id', 'id'])->on('posts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['blog_id', 'tag_id'], 'fk_34_post_tags')->references(['blog_id', 'id'])->on('tags')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tags');
    }
};
