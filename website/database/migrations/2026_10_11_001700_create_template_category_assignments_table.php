<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_category_assignments')) {
            return;
        }

        Schema::create('template_category_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('category_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['template_id', 'category_id'], 'u_0');
            $table->foreign('template_id', 'fk_20_template_category_assignments')->references('id')->on('templates')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('category_id', 'fk_21_template_category_assignments')->references('id')->on('template_categories')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_category_assignments');
    }
};
