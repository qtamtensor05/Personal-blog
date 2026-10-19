<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('platform_settings', 'revision')) {
            Schema::table('platform_settings', function (Blueprint $table): void {
                $table->unsignedBigInteger('revision')->default(1)->after('config_json');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('platform_settings', 'revision')) {
            Schema::table('platform_settings', fn (Blueprint $table) => $table->dropColumn('revision'));
        }
    }
};
