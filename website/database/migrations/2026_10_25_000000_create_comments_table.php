<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body');
            $table->enum('status', ['VISIBLE', 'OWNER_HIDDEN', 'ADMIN_HIDDEN'])->default('VISIBLE');
            $table->unsignedBigInteger('hidden_by_user_id')->nullable();
            $table->dateTime('hidden_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['post_id', 'parent_id', 'status', 'created_at'], 'i_comments_tree');
            $table->index(['parent_id', 'created_at', 'id'], 'i_comments_replies');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('parent_id')->references('id')->on('comments')->nullOnDelete();
            $table->foreign('hidden_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
