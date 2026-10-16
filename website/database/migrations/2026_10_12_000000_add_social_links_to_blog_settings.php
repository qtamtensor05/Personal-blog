<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (
            Schema::hasTable('blog_settings') &&
            !Schema::hasColumn('blog_settings', 'social_links')
        ) {
            Schema::table('blog_settings', function (Blueprint $table): void {
                $table->json('social_links')->nullable();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This schema is forward-only. Restore a backup instead of running a destructive rollback.',
        );
    }
};
