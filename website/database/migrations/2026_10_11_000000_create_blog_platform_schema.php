<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('The business schema requires MySQL 8.0.16 or newer.');
        }

        $path = database_path('sql/blog_platform_mysql.sql');
        $sql = is_file($path) ? file_get_contents($path) : false;

        if ($sql === false) {
            throw new RuntimeException(
                'Missing runtime schema at database/sql/blog_platform_mysql.sql.',
            );
        }

        foreach (
            array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [])) as $statement
        ) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This schema is forward-only. Restore a backup instead of running a destructive rollback.',
        );
    }
};
