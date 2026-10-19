<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL forbids CHECK constraints that reference columns with SET NULL FKs.
        // The old checks were removed when template references became nullable.
    }

    public function down(): void
    {
        // Nothing to roll back: the constraints were intentionally removed.
    }
};
