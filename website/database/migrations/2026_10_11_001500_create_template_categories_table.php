<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_categories')) {
            Schema::create('template_categories', function (Blueprint $table): void {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->string('name', 160);
                $table->string('slug', 160);
                $table->string('kind', 20);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
                $table->unique(['kind', 'slug'], 'u_0');
                $table
                    ->foreign('parent_id', 'fk_17_template_categories')
                    ->references('id')
                    ->on('template_categories')
                    ->onDelete('restrict')
                    ->onUpdate('restrict');
            });
        }

        $this->ensureCheckConstraint(
            'template_categories',
            'ck_template_categories_0',
            "`kind` IN ('TYPE', 'INDUSTRY', 'TOPIC')",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('template_categories');
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
