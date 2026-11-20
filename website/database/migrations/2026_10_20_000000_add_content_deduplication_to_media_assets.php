<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('media_assets', 'content_hash')) {
            Schema::table('media_assets', function (Blueprint $table): void {
                $table->char('content_hash', 64)->nullable();
                $table->string('deduplication_scope', 40)->nullable();
            });
        }

        Schema::table('media_assets', function (Blueprint $table): void {
            $table->unique(
                ['deduplication_scope', 'content_hash'],
                'u_media_content_scope_hash',
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('media_assets', 'content_hash')) {
            Schema::table('media_assets', function (Blueprint $table): void {
                $table->dropUnique('u_media_content_scope_hash');
                $table->dropColumn(['content_hash', 'deduplication_scope']);
            });
        }
    }
};
