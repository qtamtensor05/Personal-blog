<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_categories')) {
            return;
        }

        Schema::create('template_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->string('kind', 20);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['kind', 'slug'], 'u_0');
            $table->foreign('parent_id', 'fk_17_template_categories')->references('id')->on('template_categories')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement("ALTER TABLE `template_categories` ADD CONSTRAINT `ck_template_categories_0` CHECK (`kind` IN ('TYPE', 'INDUSTRY', 'TOPIC'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('template_categories');
    }
};
