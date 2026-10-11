<?php

// Test-only subprocess protocol. Never accepts a non-test database.
use App\Models\User;
use App\Modules\AccessControl\Actions\ManageAccessAction;
use App\Modules\Identity\Actions\ResetCurrentPasswordAction;
use App\Modules\Identity\Actions\UpdateAccountAction;
use App\Modules\Identity\Actions\VerifyCurrentEmailAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require __DIR__.'/../../vendor/autoload.php';
$input = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! str_ends_with($input['connection']['database'] ?? '', '_test')) {
    throw new RuntimeException('Concurrency worker requires an isolated *_test database.');
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'mysql', 'database.connections.mysql' => $input['connection'], 'cache.default' => 'array', 'queue.default' => 'sync']);
$actor = User::findOrFail($input['actor']);
if (($input['operation'] === 'reset' && ! Password::tokenExists($actor, $input['token'])) || ($input['operation'] === 'verify' && ! hash_equals(sha1($actor->email), $input['hash']))) {
    throw new RuntimeException('Race fixture must validate before waiting for the identity lock.');
}
try {
    if ($input['hold']) {
        DB::beginTransaction();
        if ($input['operation'] === 'recovery') {
            DB::table('users')->where('id', $input['actor'])->lockForUpdate()->first();
        } else {
            DB::table('platform_roles')->orderBy('id')->lockForUpdate()->get();
            if ($input['operation'] === 'email') {
                DB::table('users')->where('id', $input['actor'])->lockForUpdate()->first();
            }
        }
    }
    echo json_encode(['ready' => true, 'connection' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id]).PHP_EOL;
    fflush(STDOUT);
    if ($input['hold'] && trim(fgets(STDIN)) !== 'release') {
        throw new RuntimeException('Missing barrier release.');
    }
    if ($input['operation'] === 'verify') {
        app(VerifyCurrentEmailAction::class)->execute($actor->id, $input['hash']);
    } elseif ($input['operation'] === 'reset') {
        $status = app(ResetCurrentPasswordAction::class)->execute(['email' => $input['email'], 'token' => $input['token'], 'password' => 'ResetPassword123']);
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['token' => 'Invalidated token.']);
        }
    } elseif ($input['operation'] === 'recovery') {
        if (! AppAuthentication::make()->recoverable()->verifyRecoveryCode($input['code'], $actor)) {
            throw ValidationException::withMessages(['code' => 'Recovery code already consumed.']);
        }
    } elseif ($input['operation'] === 'email') {
        app(UpdateAccountAction::class)->execute($actor, 'email', ['email' => $input['email'], 'current_password' => 'CurrentPassword123']);
    } else {
        app(ManageAccessAction::class)->execute($actor, 'revoke', ['user_id' => $input['target'], 'role_id' => $input['role']]);
    }
    if ($input['hold']) {
        DB::commit();
    }
    echo json_encode(['result' => 'ok']).PHP_EOL;
} catch (Throwable $exception) {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    $expected = $exception instanceof ValidationException || ($exception instanceof HttpException && $exception->getStatusCode() === 403);
    echo json_encode(['result' => $expected ? 'blocked' : 'error', 'class' => $exception::class, 'database_error' => $exception instanceof QueryException ? $exception->errorInfo : null]).PHP_EOL;
    if (! $expected) {
        exit(1);
    }
}
