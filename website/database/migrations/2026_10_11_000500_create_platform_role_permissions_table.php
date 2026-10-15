<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_role_permissions')) {
            return;
        }

        Schema::create('platform_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['role_id', 'permission_id'], 'u_0');
            $table->foreign('role_id', 'fk_0_platform_role_permissions')->references('id')->on('platform_roles')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('permission_id', 'fk_2_platform_role_permissions')->references('id')->on('permissions')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_role_permissions');
    }
};
