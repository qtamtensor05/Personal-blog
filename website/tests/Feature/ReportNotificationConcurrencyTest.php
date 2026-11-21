<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

it('serializes overlapping report submissions on mysql', function (string $type): void {
    if (getenv('REPORT_CONCURRENCY_MYSQL') !== '1') {
        $this->markTestSkipped('Opt in with REPORT_CONCURRENCY_MYSQL=1 and a MySQL test connection.');
    }
    $connection = 'report_concurrency_test';
    $settings = config('database.connections.mysql');
    $settings['prefix'] = 'report_race_'.bin2hex(random_bytes(5)).'_';
    config()->set('database.connections.'.$connection, $settings);
    $schema = Schema::connection($connection);
    try {
        $schema->create('users', fn (Blueprint $table) => $table->unsignedBigInteger('id')->primary());
        $schema->create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('reporter_user_id');
            $table->string('target_type');
            $table->text('reason');
            $table->string('status');
            $table->timestamps();
        });
        $schema->create('report_notification_outbox', function (Blueprint $table): void {
            $table->unsignedBigInteger('report_id')->primary();
            $table->string('target_type');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
        $schema->create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('recipient_user_id');
            $table->string('type');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        DB::connection($connection)->table('users')->insert(['id' => 7]);
        $worker = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$settings = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
config()->set('database.connections.report_race_worker', $settings);
Illuminate\Support\Facades\DB::setDefaultConnection('report_race_worker');
$recipients = new class implements App\Application\Moderation\Contracts\ReportNotificationRecipients {
    public function userIds(): array { return [1, 2]; }
};
$outbox = new App\Infrastructure\Moderation\ReportNotificationOutbox($recipients, app(App\Application\Notifications\Contracts\NotificationPublisher::class));
$repository = new App\Infrastructure\Moderation\QueryBuilderContentReportRepository($outbox);
try {
    Illuminate\Support\Facades\DB::transaction(function () use ($repository, $argv): void {
        if ($argv[1] === 'COMMENT') {
            $repository->createComment(10, 20, 30, 7, 'Concurrent spam report');
        } else {
            $repository->create(10, $argv[1] === 'POST' ? 20 : null, 7, 'Concurrent spam report');
        }
        // Keep the first transaction open while the other worker requests the same reporter lock.
        usleep(1000000);
    });
    echo 'created';
} catch (DomainException $exception) {
    echo 'duplicate';
}
PHP;
        $workers = [];
        foreach (range(1, 2) as $index) {
            $process = new Process([PHP_BINARY, '-r', $worker, $type], base_path());
            $process->setInput(json_encode($settings, JSON_THROW_ON_ERROR));
            $process->setTimeout(20);
            $process->start();
            $workers[] = $process;
        }
        $outputs = [];
        foreach ($workers as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
            $outputs[] = $process->getOutput();
        }
        sort($outputs);
        expect($outputs)->toBe(['created', 'duplicate'])
            ->and(DB::connection($connection)->table('content_reports')->count())->toBe(1)
            ->and(DB::connection($connection)->table('report_notification_outbox')->count())->toBe(1)
            ->and(DB::connection($connection)->table('notifications')->count())->toBe(2);
    } finally {
        foreach (['notifications', 'report_notification_outbox', 'content_reports', 'users'] as $table) {
            $schema->dropIfExists($table);
        }
        DB::purge($connection);
    }
})->with(['BLOG', 'POST', 'COMMENT']);
