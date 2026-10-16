<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('blogs')) {
            return;
        }

        Schema::create('blogs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name', 160);
            $table->string('slug', 160)->unique('u_0');
            $table->text('description')->nullable();
            $table
                ->enum('status', ['DRAFT', 'PUBLISHED', 'HIDDEN', 'SUSPENDED', 'ARCHIVED'])
                ->default('DRAFT');
            $table->enum('visibility', ['PUBLIC', 'UNLISTED', 'PRIVATE'])->default('PUBLIC');
            $table->boolean('discoverable')->default(true);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['owner_user_id', 'status'], 'i_0');
            $table->index(['status', 'visibility', 'discoverable'], 'i_1');
            $table
                ->foreign('owner_user_id', 'fk_12_blogs')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
