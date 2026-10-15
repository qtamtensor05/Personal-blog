<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_designs')) {
            return;
        }

        Schema::create('blog_designs', function (Blueprint $table): void {
            $table->unsignedBigInteger('blog_id')->primary();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('draft_template_id')->nullable();
            $table->json('published_data')->nullable();
            $table->json('draft_data')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->foreign('blog_id', 'fk_55_blog_designs')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('template_id', 'fk_56_blog_designs')->references('id')->on('templates')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('draft_template_id', 'fk_57_blog_designs')->references('id')->on('templates')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('updated_by', 'fk_58_blog_designs')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement('ALTER TABLE `blog_designs` ADD CONSTRAINT `ck_blog_designs_0` CHECK (`published_data` IS NOT NULL OR `draft_data` IS NOT NULL)');
        DB::statement('ALTER TABLE `blog_designs` ADD CONSTRAINT `ck_blog_designs_1` CHECK ((`template_id` IS NULL AND `published_data` IS NULL) OR (`template_id` IS NOT NULL AND `published_data` IS NOT NULL))');
        DB::statement('ALTER TABLE `blog_designs` ADD CONSTRAINT `ck_blog_designs_2` CHECK ((`draft_template_id` IS NULL AND `draft_data` IS NULL) OR (`draft_template_id` IS NOT NULL AND `draft_data` IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_designs');
    }
};
