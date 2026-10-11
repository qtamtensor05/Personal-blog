<?php

namespace Tests\Concurrency;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * TASK-02 § 2.2 – Concurrent slug race tests.
 *
 * Verifies that concurrent requests racing to claim the same blog slug are
 * serialized by the user-row lock in CreateBlogAction and that the DB unique
 * constraint converts any remaining race window into a user-visible error
 * (not an HTTP 500).
 *
 * Also verifies that the user-row lock prevents two simultaneous blog-create
 * requests from the same owner from both passing the quota check.
 *
 * Requires MySQL (blog_test database). Uses two real OS processes / DB connections,
 * the same infrastructure as AccessRaceTest.
 */
class SlugRaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (
            ! app()->environment('testing')
            || DB::getDriverName() !== 'mysql'
            || ! str_ends_with(DB::connection()->getDatabaseName(), '_test')
        ) {
            throw new \RuntimeException('Refusing to reset a non-test database.');
        }
        $this->artisan('migrate:fresh')->assertSuccessful();
        $this->seed(PlatformSeeder::class);
    }

    /** Two owners simultaneously try to create a blog with the same slug; only one must succeed. */
    public function test_two_concurrent_create_requests_for_the_same_slug_leave_exactly_one_blog(): void
    {
        $ownerA = User::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'email' => 'slug-owner-a@example.test',
            'password' => Hash::make('Password123'),
        ]);
        $ownerB = User::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'email' => 'slug-owner-b@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->raceSlug(
            ['actor' => $ownerA->id, 'operation' => 'create_blog', 'slug' => 'racing-slug', 'name' => 'Blog A'],
            ['actor' => $ownerB->id, 'operation' => 'create_blog', 'slug' => 'racing-slug', 'name' => 'Blog B'],
        );

        $this->assertSame(1, DB::table('blogs')->where('slug', 'racing-slug')->count());
    }

    /** Same owner creates blogs concurrently; quota (maxBlogsPerUser=3) is enforced. */
    public function test_concurrent_create_requests_from_same_owner_are_serialized_by_user_lock(): void
    {
        // Set quota to 1 so the second concurrent request sees count=1 and is blocked.
        DB::table('platform_settings')
            ->where('id', 1)
            ->update(['config_json' => json_encode([
                'schemaVersion' => 1,
                'branding' => ['name' => 'Test'],
                'seo' => ['title' => 'Test', 'description' => ''],
                'discovery' => ['enabled' => true],
                'limits' => [
                    'maxBlogsPerUser' => 1,
                    'defaultStorageQuotaBytes' => 104857600,
                ],
                'features' => ['registrationEnabled' => true, 'customDomainsEnabled' => false],
                'security' => ['requireAdmin2fa' => true, 'requireVerifiedEmailForBlog' => true],
            ])]);

        $owner = User::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'email' => 'quota-race@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->raceSlug(
            ['actor' => $owner->id, 'operation' => 'create_blog', 'slug' => 'quota-blog-a', 'name' => 'Blog A'],
            ['actor' => $owner->id, 'operation' => 'create_blog', 'slug' => 'quota-blog-b', 'name' => 'Blog B'],
        );

        $this->assertSame(1, DB::table('blogs')
            ->where('owner_user_id', $owner->id)
            ->count());
    }

    /** A slug change racing against another slug change in settings lands exactly one winner. */
    public function test_concurrent_slug_update_on_settings_leaves_exactly_one_winner(): void
    {
        $ownerA = User::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'email' => 'slug-update-a@example.test',
            'password' => Hash::make('Password123'),
        ]);
        $ownerB = User::factory()->create([
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'email' => 'slug-update-b@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $blogA = DB::table('blogs')->insertGetId([
            'owner_user_id' => $ownerA->id,
            'name' => 'Blog A',
            'slug' => 'blog-a-before',
            'status' => 'PUBLISHED',
            'visibility' => 'PUBLIC',
            'discoverable' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('blog_settings')->insert([
            'blog_id' => $blogA,
            'locale' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'posts_per_page' => 10,
            'storage_quota_bytes' => 104857600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blogB = DB::table('blogs')->insertGetId([
            'owner_user_id' => $ownerB->id,
            'name' => 'Blog B',
            'slug' => 'blog-b-before',
            'status' => 'PUBLISHED',
            'visibility' => 'PUBLIC',
            'discoverable' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('blog_settings')->insert([
            'blog_id' => $blogB,
            'locale' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'posts_per_page' => 10,
            'storage_quota_bytes' => 104857600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Both try to rename their blog to the same target slug simultaneously.
        $this->raceSlug(
            [
                'actor' => $ownerA->id,
                'operation' => 'update_slug',
                'blog_id' => $blogA,
                'slug' => 'contested-slug',
            ],
            [
                'actor' => $ownerB->id,
                'operation' => 'update_slug',
                'blog_id' => $blogB,
                'slug' => 'contested-slug',
            ],
        );

        $this->assertSame(1, DB::table('blogs')->where('slug', 'contested-slug')->count());
    }

    // -----------------------------------------------------------------------
    // Infrastructure (mirrors AccessRaceTest)
    // -----------------------------------------------------------------------

    private function raceSlug(array $first, array $second): void
    {
        $processes = [];
        try {
            [$one, $barrier] = $this->worker($first, true);
            $processes[] = $one;
            $this->waitFor(
                fn () => str_contains($one->getOutput(), '"ready":true'),
                fn () => 'First worker: '.$one->getErrorOutput().$one->getOutput(),
            );
            [$two] = $this->worker($second, false);
            $processes[] = $two;
            $this->waitFor(
                fn () => str_contains($two->getOutput(), '"ready":true'),
                fn () => 'Second worker: '.$two->getErrorOutput().$two->getOutput(),
            );

            $id = json_decode(explode("\n", trim($two->getOutput()))[0], true)['connection'];
            $blockingId = json_decode(explode("\n", trim($one->getOutput()))[0], true)['connection'];

            $this->waitFor(function () use ($one, $two, $id, $blockingId): bool {
                if (! $one->isRunning() || ! $two->isRunning()) {
                    $this->fail(
                        'Worker exited before lock contention: '
                        .$one->getErrorOutput().$one->getOutput()
                        .$two->getErrorOutput().$two->getOutput(),
                    );
                }

                // In MySQL 8.4 InnoDB, waiter is reliably found via performance_schema.threads,
                // while blocker transaction is in information_schema.innodb_trx.
                $viaTrx = DB::table('performance_schema.data_lock_waits as waits')
                    ->join('performance_schema.threads as waiter', 'waiter.THREAD_ID', '=', 'waits.REQUESTING_THREAD_ID')
                    ->join('information_schema.innodb_trx as blocker_trx', 'blocker_trx.trx_id', '=', 'waits.BLOCKING_ENGINE_TRANSACTION_ID')
                    ->where('waiter.PROCESSLIST_ID', $id)
                    ->where('blocker_trx.trx_mysql_thread_id', $blockingId)
                    ->exists();

                if ($viaTrx) {
                    return true;
                }

                return DB::table('performance_schema.data_lock_waits as waits')
                    ->join('performance_schema.threads as waiter', 'waiter.THREAD_ID', '=', 'waits.REQUESTING_THREAD_ID')
                    ->join('performance_schema.threads as blocker', 'blocker.THREAD_ID', '=', 'waits.BLOCKING_THREAD_ID')
                    ->where('waiter.PROCESSLIST_ID', $id)
                    ->where('blocker.PROCESSLIST_ID', $blockingId)
                    ->exists();
            }, fn () => 'Lock wait: '.$two->getErrorOutput().$two->getOutput());

            $this->assertTrue($two->isRunning(), 'Second connection must wait on the first transaction.');
            $barrier->write("release\n");
            $barrier->close();
            $one->wait();
            $two->wait();

            $this->assertTrue($one->isSuccessful(), $one->getErrorOutput().$one->getOutput());
            $this->assertTrue($two->isSuccessful(), $two->getErrorOutput().$two->getOutput());
            $this->assertStringContainsString('"result":"ok"', $one->getOutput());
            $this->assertStringContainsString('"result":"blocked"', $two->getOutput());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
        }
    }

    private function worker(array $operation, bool $hold): array
    {
        $stream = new InputStream;
        $stream->write(
            json_encode(
                $operation + ['hold' => $hold, 'connection' => DB::connection()->getConfig()],
                JSON_THROW_ON_ERROR,
            )."\n",
        );
        $command = [PHP_BINARY];
        if (PHP_OS_FAMILY === 'Windows') {
            array_push($command, '-d', 'extension=intl');
        }
        $command[] = base_path('tests/Support/slug-race-worker.php');
        $process = new Process(
            $command,
            base_path(),
            ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key')],
            $stream,
            30,
        );
        $process->start();

        return [$process, $stream];
    }

    private function waitFor(callable $ready, callable $diagnostic): void
    {
        $deadline = microtime(true) + 15;
        while (! $ready()) {
            if (microtime(true) > $deadline) {
                $this->fail('Concurrency barrier timed out. '.$diagnostic());
            }
            usleep(20000);
        }
    }
}
