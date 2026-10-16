<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_platform_roles')) {
            return;
        }

        Schema::create('user_platform_roles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('granted_by');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['user_id', 'role_id'], 'u_0');
            $table
                ->foreign('role_id', 'fk_1_user_platform_roles')
                ->references('id')
                ->on('platform_roles')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('user_id', 'fk_3_user_platform_roles')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('granted_by', 'fk_4_user_platform_roles')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_platform_roles');
    }
};
