<?php

/**
 * Concurrency worker for TASK-02 slug race tests.
 * Never accepts a non-test database.
 *
 * Supported operations:
 *   create_blog  – CreateBlogAction::execute with given slug/name
 *   update_slug  – SaveBlogSettingsAction::execute to change blog slug
 */

use App\Models\User;
use App\Modules\Blogs\Actions\CreateBlogAction;
use App\Modules\Blogs\Actions\SaveBlogSettingsAction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require __DIR__.'/../../vendor/autoload.php';

$input = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! str_ends_with($input['connection']['database'] ?? '', '_test')) {
    throw new RuntimeException('Slug race worker requires an isolated *_test database.');
}

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'mysql',
    'database.connections.mysql' => $input['connection'],
    'cache.default' => 'array',
    'queue.default' => 'sync',
]);

$actor = User::findOrFail($input['actor']);

try {
    if ($input['hold']) {
        // Begin transaction and execute action so unique constraint index or user lock is held
        DB::beginTransaction();
        if ($input['operation'] === 'create_blog') {
            app(CreateBlogAction::class)->execute($actor, [
                'name' => $input['name'],
                'slug' => $input['slug'],
            ]);
        } elseif ($input['operation'] === 'update_slug') {
            $blog = DB::table('blogs')->where('id', $input['blog_id'])->first();
            app(SaveBlogSettingsAction::class)->execute($actor, (int) $input['blog_id'], [
                'name' => $blog->name,
                'slug' => $input['slug'],
                'visibility' => $blog->visibility,
                'posts_per_page' => 10,
            ]);
        }
    }

    echo json_encode(['ready' => true, 'connection' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id]).PHP_EOL;
    fflush(STDOUT);

    if ($input['hold']) {
        if (trim(fgets(STDIN)) !== 'release') {
            throw new RuntimeException('Missing barrier release.');
        }
        DB::commit();
    } else {
        if ($input['operation'] === 'create_blog') {
            app(CreateBlogAction::class)->execute($actor, [
                'name' => $input['name'],
                'slug' => $input['slug'],
            ]);
        } elseif ($input['operation'] === 'update_slug') {
            $blog = DB::table('blogs')->where('id', $input['blog_id'])->first();
            app(SaveBlogSettingsAction::class)->execute($actor, (int) $input['blog_id'], [
                'name' => $blog->name,
                'slug' => $input['slug'],
                'visibility' => $blog->visibility,
                'posts_per_page' => 10,
            ]);
        } else {
            throw new RuntimeException('Unknown operation: '.$input['operation']);
        }
    }

    echo json_encode(['result' => 'ok']).PHP_EOL;
} catch (Throwable $exception) {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    $expected = $exception instanceof ValidationException
        || ($exception instanceof HttpException && $exception->getStatusCode() === 403);

    echo json_encode([
        'result' => $expected ? 'blocked' : 'error',
        'class' => $exception::class,
        'message' => $exception->getMessage(),
        'database_error' => $exception instanceof QueryException ? $exception->errorInfo : null,
    ]).PHP_EOL;

    if (! $expected) {
        exit(1);
    }
}
