<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tags')) {
            return;
        }

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'slug'], 'u_0');
            $table->unique(['blog_id', 'id'], 'u_1');
            $table->foreign('blog_id', 'fk_30_tags')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
