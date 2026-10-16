<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('The business schema requires MySQL 8.0.16 or newer.');
        }

        $serverVersion = (string) DB::scalar('SELECT VERSION()');

        if (str_contains(strtolower($serverVersion), 'mariadb')
            || preg_match('/^\d+(?:\.\d+){0,2}/', $serverVersion, $matches) !== 1
            || version_compare($matches[0], '8.0.16', '<')) {
            throw new RuntimeException(sprintf(
                'The business schema requires MySQL 8.0.16 or newer; connected server reports %s.',
                $serverVersion,
            ));
        }

        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('name', 160);
            $table->string('email', 254)->collation('utf8mb4_unicode_ci')->unique('u_0');
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('status', ['PENDING_VERIFICATION', 'ACTIVE', 'LOCKED', 'SUSPENDED', 'DISABLED'])->default('PENDING_VERIFICATION');
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('avatar_media_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
