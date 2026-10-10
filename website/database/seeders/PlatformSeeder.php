<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('docs/database/seed_roles_permissions.sql'));
        if ($sql === false) {
            throw new \RuntimeException('Missing canonical platform seed.');
        }
        $sql = preg_replace('/^START TRANSACTION;|COMMIT;$/m', '', $sql);
        DB::transaction(fn () => DB::unprepared($sql));
    }
}
