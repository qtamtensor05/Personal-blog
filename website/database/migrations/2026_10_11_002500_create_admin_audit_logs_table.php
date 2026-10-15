<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_audit_logs')) {
            return;
        }

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_user_id');
            $table->unsignedBigInteger('blog_id')->nullable();
            $table->string('action', 120);
            $table->string('resource_type', 80);
            $table->string('resource_id', 80)->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['actor_user_id', 'created_at'], 'i_0');
            $table->index(['blog_id', 'created_at'], 'i_1');
            $table->foreign('actor_user_id', 'fk_47_admin_audit_logs')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('blog_id', 'fk_48_admin_audit_logs')->references('id')->on('blogs')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
