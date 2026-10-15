<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_settings')) {
            return;
        }

        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->default(1)->primary();
            $table->json('config_json');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->foreign('updated_by', 'fk_49_platform_settings')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });

        DB::statement('ALTER TABLE `platform_settings` ADD CONSTRAINT `ck_platform_settings_0` CHECK (`id` = 1)');
        DB::statement("ALTER TABLE `platform_settings` ADD CONSTRAINT `ck_platform_settings_1` CHECK (JSON_TYPE(`config_json`) = 'OBJECT')");
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
