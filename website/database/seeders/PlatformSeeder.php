<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('sql/seed_roles_permissions.sql');
        $sql = is_file($path) ? file_get_contents($path) : false;
        if ($sql === false) {
            throw new \RuntimeException('Missing runtime platform seed at database/sql/seed_roles_permissions.sql.');
        }
        $sql = preg_replace('/^START TRANSACTION;|COMMIT;$/m', '', $sql);
        DB::transaction(fn () => DB::unprepared($sql));
    }
}
