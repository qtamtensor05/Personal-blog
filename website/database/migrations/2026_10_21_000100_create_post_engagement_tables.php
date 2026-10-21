<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('post_id');
            $table->char('viewer_key', 64);
            $table->date('view_date');
            $table->dateTime('created_at');
            $table->primary(['post_id', 'viewer_key', 'view_date']);
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        Schema::create('post_like_notification_cooldowns', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('liker_user_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('recipient_user_id');
            $table->dateTime('last_notified_at');
            $table->primary(['liker_user_id', 'post_id', 'recipient_user_id'], 'pk_like_notice_cooldown');
            $table->foreign('liker_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->foreign('recipient_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_like_notification_cooldowns');
        Schema::dropIfExists('post_views');
    }
};
