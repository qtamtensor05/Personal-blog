<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            return;
        }

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique('u_0');
            $table->string('scope', 20);
            $table->text('description')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE `permissions` ADD CONSTRAINT `ck_permissions_0` CHECK (`scope` = 'PLATFORM')");
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
