<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $table): void {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->enum('scope', ['PLATFORM', 'BLOG', 'USER']);
                $table->unsignedBigInteger('blog_id')->nullable();
                $table->unsignedBigInteger('uploaded_by');
                $table->string('cloud_name', 120);
                $table->string('cloudinary_asset_id', 128)->nullable();
                $table->string('cloudinary_public_id')->nullable();
                $table->unsignedBigInteger('cloudinary_version')->nullable();
                $table->string('resource_type', 20)->default('image');
                $table->string('delivery_type', 40)->default('upload');
                $table->string('secure_url', 2048)->nullable();
                $table->string('original_name');
                $table->string('format', 40)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedBigInteger('bytes')->nullable();
                $table->string('alt_text', 500)->nullable();
                $table
                    ->enum('status', ['UPLOADING', 'READY', 'FAILED', 'DELETING', 'DELETED'])
                    ->default('UPLOADING');
                $table->dateTime('deleted_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
                $table->unique(['cloud_name', 'cloudinary_asset_id'], 'u_0');
                $table->index(['blog_id', 'status', 'created_at'], 'i_0');
                $table
                    ->foreign('blog_id', 'fk_35_media_assets')
                    ->references('id')
                    ->on('blogs')
                    ->onDelete('restrict')
                    ->onUpdate('restrict');
                $table
                    ->foreign('uploaded_by', 'fk_36_media_assets')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('restrict');
            });
        }

        $this->ensureCheckConstraint(
            'media_assets',
            'ck_media_assets_0',
            "(`scope` = 'BLOG' AND `blog_id` IS NOT NULL) OR (`scope` IN ('PLATFORM', 'USER') AND `blog_id` IS NULL)",
        );

        if (!$this->foreignKeyExists('users', 'fk_37_users')) {
            Schema::table('users', function (Blueprint $table): void {
                $table
                    ->foreign('avatar_media_id', 'fk_37_users')
                    ->references('id')
                    ->on('media_assets')
                    ->onDelete('restrict')
                    ->onUpdate('restrict');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && $this->foreignKeyExists('users', 'fk_37_users')) {
            Schema::table('users', fn(Blueprint $table) => $table->dropForeign('fk_37_users'));
        }

        Schema::dropIfExists('media_assets');
    }

    private function ensureCheckConstraint(string $table, string $name, string $expression): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'CHECK')
            ->exists();

        if (!$exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
        }
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
