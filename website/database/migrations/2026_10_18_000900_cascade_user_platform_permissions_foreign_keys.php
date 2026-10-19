<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_platform_permissions', function (Blueprint $table): void {
            $table->dropForeign('fk_0_user_platform_permissions');
            $table->dropForeign('fk_1_user_platform_permissions');
            $table->dropForeign('fk_2_user_platform_permissions');

            $table->foreign('user_id', 'fk_0_user_platform_permissions')
                ->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign('permission_id', 'fk_1_user_platform_permissions')
                ->references('id')->on('permissions')->onDelete('cascade')->onUpdate('restrict');
            $table->foreign('granted_by', 'fk_2_user_platform_permissions')
                ->references('id')->on('users')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('user_platform_permissions', function (Blueprint $table): void {
            $table->dropForeign('fk_0_user_platform_permissions');
            $table->dropForeign('fk_1_user_platform_permissions');
            $table->dropForeign('fk_2_user_platform_permissions');

            $table->foreign('user_id', 'fk_0_user_platform_permissions')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('permission_id', 'fk_1_user_platform_permissions')
                ->references('id')->on('permissions')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('granted_by', 'fk_2_user_platform_permissions')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }
};
