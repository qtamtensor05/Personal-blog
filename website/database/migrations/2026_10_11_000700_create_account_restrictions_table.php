<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_restrictions')) {
            return;
        }

        Schema::create('account_restrictions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('kind', ['LOCK', 'SUSPEND', 'DISABLE']);
            $table->enum('status', ['ACTIVE', 'REVOKED', 'EXPIRED'])->default('ACTIVE');
            $table->text('reason');
            $table->unsignedBigInteger('issued_by');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['user_id', 'status', 'ends_at'], 'i_0');
            $table->foreign('user_id', 'fk_6_account_restrictions')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('issued_by', 'fk_7_account_restrictions')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('revoked_by', 'fk_8_account_restrictions')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_restrictions');
    }
};
