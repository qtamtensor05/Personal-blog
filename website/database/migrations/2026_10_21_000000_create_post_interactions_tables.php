<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['post_likes', 'post_bookmarks'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('post_id');
                $table->dateTime('created_at')->useCurrent();
                $table->primary(['user_id', 'post_id']);
                $table->index('post_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_bookmarks');
        Schema::dropIfExists('post_likes');
    }
};
