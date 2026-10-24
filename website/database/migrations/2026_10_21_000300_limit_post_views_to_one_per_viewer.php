<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the earliest view for each post/viewer pair before enforcing lifetime uniqueness.
        $duplicates = DB::table('post_views')
            ->select('post_id', 'viewer_key')
            ->selectRaw('MIN(view_date) as first_view_date')
            ->groupBy('post_id', 'viewer_key')
            ->havingRaw('COUNT(*) > 1')
            ->cursor();

        foreach ($duplicates as $duplicate) {
            DB::table('post_views')
                ->where('post_id', $duplicate->post_id)
                ->where('viewer_key', $duplicate->viewer_key)
                ->where('view_date', '!=', $duplicate->first_view_date)
                ->delete();
        }

        Schema::table('post_views', function (Blueprint $table): void {
            $table->unique(['post_id', 'viewer_key'], 'post_views_post_viewer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table): void {
            $table->dropUnique('post_views_post_viewer_unique');
        });
    }
};
