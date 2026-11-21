<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_notification_outbox', function (Blueprint $table): void {
            $table->unsignedBigInteger('report_id')->primary();
            $table->string('target_type', 16);
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
            $table->foreign('report_id')->references('id')->on('content_reports')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_notification_outbox');
    }
};
