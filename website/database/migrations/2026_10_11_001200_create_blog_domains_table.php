<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_domains')) {
            return;
        }

        Schema::create('blog_domains', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('hostname', 253)->unique('u_0');
            $table
                ->enum('status', ['PENDING', 'VERIFIED', 'ACTIVE', 'FAILED', 'DISABLED'])
                ->default('PENDING');
            $table->char('verification_token_hash', 64);
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->unique(['blog_id', 'id'], 'u_1');
            $table
                ->foreign('blog_id', 'fk_16_blog_domains')
                ->references('id')
                ->on('blogs')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_domains');
    }
};
