<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_reports')) {
            return;
        }

        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->enum('target_type', ['BLOG', 'POST']);
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->text('reason');
            $table->enum('status', ['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'])->default('OPEN');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_note')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['status', 'created_at'], 'i_0');
            $table->foreign('blog_id', 'fk_42_content_reports')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['blog_id', 'post_id'], 'fk_43_content_reports')->references(['blog_id', 'id'])->on('posts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('reporter_user_id', 'fk_44_content_reports')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('assigned_to', 'fk_45_content_reports')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('resolved_by', 'fk_46_content_reports')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement("ALTER TABLE `content_reports` ADD CONSTRAINT `ck_content_reports_0` CHECK ((`target_type` = 'BLOG' AND `post_id` IS NULL) OR (`target_type` = 'POST' AND `post_id` IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};
