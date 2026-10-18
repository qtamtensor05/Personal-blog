<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_platform_permissions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->enum('effect', ['GRANT', 'DENY']);
            $table->unsignedBigInteger('granted_by');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['user_id', 'permission_id'], 'u_0');
            $table->foreign('user_id', 'fk_0_user_platform_permissions')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('permission_id', 'fk_1_user_platform_permissions')
                ->references('id')->on('permissions')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('granted_by', 'fk_2_user_platform_permissions')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_platform_permissions');
    }
};
