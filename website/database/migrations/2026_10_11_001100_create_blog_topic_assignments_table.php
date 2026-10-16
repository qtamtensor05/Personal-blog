<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('blog_topic_assignments')) {
            return;
        }

        Schema::create('blog_topic_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('topic_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'topic_id'], 'u_0');
            $table
                ->foreign('blog_id', 'fk_13_blog_topic_assignments')
                ->references('id')
                ->on('blogs')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('topic_id', 'fk_14_blog_topic_assignments')
                ->references('id')
                ->on('blog_topics')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_topic_assignments');
    }
};
