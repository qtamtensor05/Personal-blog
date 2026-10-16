<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'slug'], 'u_0');
            $table->unique(['blog_id', 'id'], 'u_1');
            $table
                ->foreign('blog_id', 'fk_28_categories')
                ->references('id')
                ->on('blogs')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign(['blog_id', 'parent_id'], 'fk_29_categories')
                ->references(['blog_id', 'id'])
                ->on('categories')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
