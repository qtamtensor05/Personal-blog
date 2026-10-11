<?php

// Test-only subprocess protocol. Never accepts a non-test database.
use App\Models\User;
use App\Modules\AccessControl\Actions\ManageAccessAction;
use App\Modules\Identity\Actions\UpdateAccountAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
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
try {
    if ($input['hold']) {
        DB::beginTransaction();
        if ($input['operation'] === 'recovery') {
            DB::table('users')->where('id', $input['actor'])->lockForUpdate()->first();
        } else {
            DB::table('platform_roles')->orderBy('id')->lockForUpdate()->get();
        }
    }
    echo json_encode(['ready' => true, 'connection' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id]).PHP_EOL;
    fflush(STDOUT);
    if ($input['hold'] && trim(fgets(STDIN)) !== 'release') {
        throw new RuntimeException('Missing barrier release.');
    }
    if ($input['operation'] === 'recovery') {
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
    echo json_encode(['result' => $expected ? 'blocked' : 'error', 'class' => $exception::class]).PHP_EOL;
    if (! $expected) {
        exit(1);
    }
}
