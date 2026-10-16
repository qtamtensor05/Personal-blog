<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table): void {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->unsignedTinyInteger('id')->default(1)->primary();
                $table->json('config_json');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
                $table
                    ->foreign('updated_by', 'fk_49_platform_settings')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('restrict');
            });
        }

        $this->ensureCheckConstraint('platform_settings', 'ck_platform_settings_0', '`id` = 1');
        $this->ensureCheckConstraint(
            'platform_settings',
            'ck_platform_settings_1',
            "JSON_TYPE(`config_json`) = 'OBJECT'",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }

    private function ensureCheckConstraint(string $table, string $name, string $expression): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'CHECK')
            ->exists();

        if (! $exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
        }
    }
};
