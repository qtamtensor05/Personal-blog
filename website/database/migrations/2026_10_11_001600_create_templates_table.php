<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('templates')) {
            return;
        }

        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160)->unique('u_0');
            $table->text('description')->nullable();
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'HIDDEN', 'RETIRED'])->default('DRAFT');
            $table->unsignedBigInteger('preview_media_id')->nullable();
            $table->string('demo_url', 2048)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedInteger('version_number')->default(0);
            $table->json('template_data')->nullable();
            $table->json('draft_data')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['status', 'created_at'], 'i_0');
            $table->foreign('created_by', 'fk_18_templates')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('updated_by', 'fk_19_templates')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('preview_media_id', 'fk_41_templates')->references('id')->on('media_assets')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('published_by', 'fk_53_templates')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement('ALTER TABLE `templates` ADD CONSTRAINT `ck_templates_0` CHECK (`template_data` IS NOT NULL OR `draft_data` IS NOT NULL)');
        DB::statement("ALTER TABLE `templates` ADD CONSTRAINT `ck_templates_1` CHECK (`status` <> 'PUBLISHED' OR `template_data` IS NOT NULL)");
        DB::statement('ALTER TABLE `templates` ADD CONSTRAINT `ck_templates_2` CHECK ((`template_data` IS NULL AND `version_number` = 0) OR (`template_data` IS NOT NULL AND `version_number` > 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
