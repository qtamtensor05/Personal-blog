<?php

namespace Tests\Concurrency;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AccessRaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! app()->environment('testing') || DB::getDriverName() !== 'mysql' || ! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to reset a non-test database.');
        }
        // Committed fixtures are required: RefreshDatabase transactions are invisible to subprocesses.
        $this->artisan('migrate:fresh')->assertSuccessful();
        $this->seed(PlatformSeeder::class);
    }

    public function test_two_email_changes_preserve_one_available_super_admin(): void
    {
        [$a, $b, $role] = $this->admins();
        $this->race(['actor' => $a, 'operation' => 'email', 'email' => 'first@example.test'], ['actor' => $b, 'operation' => 'email', 'email' => 'second@example.test']);
        $this->assertSame(1, DB::table('users')->whereIn('id', [$a, $b])->where('status', 'ACTIVE')->count());
        $this->assertSame(1, DB::table('admin_audit_logs')->where('action', 'identity.email.updated')->count());
    }

    public function test_cross_revocation_rechecks_the_waiting_actors_permissions(): void
    {
        [$a, $b, $role] = $this->admins();
        $this->race(['actor' => $a, 'operation' => 'revoke', 'target' => $b, 'role' => $role], ['actor' => $b, 'operation' => 'revoke', 'target' => $a, 'role' => $role]);
        $this->assertSame(1, DB::table('user_platform_roles')->where('role_id', $role)->count());
        $this->assertSame(1, DB::table('admin_audit_logs')->where('action', 'access.admin.revoke')->count());
    }

    public function test_email_change_and_revocation_share_the_same_lock_boundary(): void
    {
        [$a, $b, $role] = $this->admins();
        $this->race(['actor' => $a, 'operation' => 'revoke', 'target' => $b, 'role' => $role], ['actor' => $a, 'operation' => 'email', 'email' => 'first@example.test']);
        $this->assertSame(2, DB::table('users')->whereIn('id', [$a, $b])->where('status', 'ACTIVE')->count());
        $this->assertSame(1, DB::table('user_platform_roles')->where('role_id', $role)->count());
    }

    public function test_the_same_recovery_code_cannot_be_consumed_on_two_connections(): void
    {
        [$a] = $this->admins();
        $user = User::findOrFail($a);
        $provider = AppAuthentication::make()->recoverable();
        $codes = $provider->generateRecoveryCodes();
        $provider->saveRecoveryCodes($user, $codes);
        $operation = ['actor' => $a, 'operation' => 'recovery', 'code' => $codes[0]];
        $this->race($operation, $operation);
        $this->assertCount(count($codes) - 1, $user->fresh()->getAppAuthenticationRecoveryCodes());
    }

    public function test_verification_rechecks_email_after_waiting_for_a_change(): void
    {
        [$a] = $this->admins();
        $old = User::findOrFail($a);
        $this->race(['actor' => $a, 'operation' => 'email', 'email' => 'changed@example.test'], ['actor' => $a, 'operation' => 'verify', 'hash' => sha1($old->email)]);
        $this->assertDatabaseHas('users', ['id' => $a, 'email' => 'changed@example.test', 'email_verified_at' => null, 'status' => 'PENDING_VERIFICATION']);
    }

    public function test_reset_rechecks_a_prevalidated_token_after_email_change(): void
    {
        [$a] = $this->admins();
        $old = User::findOrFail($a);
        $token = Password::createToken($old);
        $this->race(['actor' => $a, 'operation' => 'email', 'email' => 'changed@example.test'], ['actor' => $a, 'operation' => 'reset', 'email' => $old->email, 'token' => $token]);
        $this->assertSame($old->password, $old->fresh()->password);
    }

    private function admins(): array
    {
        $role = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
        $ids = [];
        foreach (['a', 'b'] as $name) {
            $user = User::factory()->create(['status' => 'ACTIVE', 'email_verified_at' => now(), 'email' => $name.'@example.test', 'password' => Hash::make('CurrentPassword123')]);
            DB::table('user_platform_roles')->insert(['user_id' => $user->id, 'role_id' => $role, 'granted_by' => $user->id]);
            $ids[] = $user->id;
        }

        return [...$ids, $role];
    }

    private function race(array $first, array $second): void
    {
        $processes = [];
        try {
            [$one, $barrier] = $this->worker($first, true);
            $processes[] = $one;
            $this->waitFor(fn () => str_contains($one->getOutput(), '"ready":true'), fn () => 'First worker: '.$one->getErrorOutput().$one->getOutput());
            [$two] = $this->worker($second, false);
            $processes[] = $two;
            $this->waitFor(fn () => str_contains($two->getOutput(), '"ready":true'), fn () => 'Second worker: '.$two->getErrorOutput().$two->getOutput());
            $id = json_decode(explode("\n", trim($two->getOutput()))[0], true)['connection'];
            // Proves actual MySQL lock contention, not merely two sequential processes.
            $this->waitFor(fn () => DB::table('information_schema.innodb_trx')->where('trx_mysql_thread_id', $id)->where('trx_state', 'LOCK WAIT')->exists(), fn () => 'Lock wait: '.$two->getErrorOutput().$two->getOutput());
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
        $stream->write(json_encode($operation + ['hold' => $hold, 'connection' => DB::connection()->getConfig()], JSON_THROW_ON_ERROR)."\n");
        $command = [PHP_BINARY];
        if (PHP_OS_FAMILY === 'Windows') {
            array_push($command, '-d', 'extension=intl');
        }
        $command[] = base_path('tests/Support/access-race-worker.php');
        $process = new Process($command, base_path(), ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key')], $stream, 30);
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
