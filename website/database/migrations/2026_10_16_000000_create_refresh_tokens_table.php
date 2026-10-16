<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refresh_tokens')) {
            return;
        }

        Schema::create('refresh_tokens', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('user_id');
            // SHA-256 of the opaque token; the raw value is never stored.
            $table->char('token_hash', 64)->unique('u_0');
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('replaced_by_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['user_id', 'revoked_at'], 'i_0');
            $table
                ->foreign('user_id', 'fk_59_refresh_tokens')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            $table
                ->foreign('replaced_by_id', 'fk_60_refresh_tokens')
                ->references('id')
                ->on('refresh_tokens')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
