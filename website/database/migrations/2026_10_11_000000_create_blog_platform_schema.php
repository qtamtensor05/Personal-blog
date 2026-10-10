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

        $sql = file_get_contents(base_path('docs/database/blog_platform_mysql.sql'));

        if ($sql === false) {
            throw new RuntimeException(
                'Missing canonical schema at docs/database/blog_platform_mysql.sql.',
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
