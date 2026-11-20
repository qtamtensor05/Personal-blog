<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_reports', function (Blueprint $table): void {
            $table->unsignedBigInteger('comment_id')->nullable()->after('post_id');
            $table->index(['comment_id', 'status'], 'i_content_reports_comment');
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('restrict');
        });

        DB::statement("ALTER TABLE content_reports MODIFY target_type ENUM('BLOG','POST','COMMENT') NOT NULL");
        DB::statement('ALTER TABLE content_reports DROP CHECK ck_content_reports_0');
        DB::statement("ALTER TABLE content_reports ADD CONSTRAINT ck_content_reports_0 CHECK ((target_type = 'BLOG' AND post_id IS NULL AND comment_id IS NULL) OR (target_type = 'POST' AND post_id IS NOT NULL AND comment_id IS NULL) OR (target_type = 'COMMENT' AND post_id IS NOT NULL AND comment_id IS NOT NULL))");
    }

    public function down(): void
    {
        if (DB::table('content_reports')->where('target_type', 'COMMENT')->exists()) {
            throw new RuntimeException('Cannot roll back comment reports while COMMENT report data exists. Archive or migrate those rows first.');
        }

        DB::statement('ALTER TABLE content_reports DROP CHECK ck_content_reports_0');
        DB::statement("ALTER TABLE content_reports ADD CONSTRAINT ck_content_reports_0 CHECK ((target_type = 'BLOG' AND post_id IS NULL) OR (target_type = 'POST' AND post_id IS NOT NULL))");
        DB::statement("ALTER TABLE content_reports MODIFY target_type ENUM('BLOG','POST') NOT NULL");
        Schema::table('content_reports', function (Blueprint $table): void {
            $table->dropForeign(['comment_id']);
            $table->dropIndex('i_content_reports_comment');
            $table->dropColumn('comment_id');
        });
    }
};
