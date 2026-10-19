<?php

declare(strict_types=1);

namespace App\Presentation\Web\Middleware;

use App\Application\Domains\Contracts\BlogDomainRepository;
use App\Presentation\Web\Controllers\PublicBlogController;
use App\Presentation\Web\Controllers\PublicPageController;
use App\Presentation\Web\Controllers\PublicPostController;
use App\Providers\DomainServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: requests for an ACTIVE custom domain are answered with the public
 * blog pages; every other path on that host is 404. The workspace, admin and auth routes
 * stay on the platform host only.
 *
 * Runs before sessions start, so visitors on a custom domain are always anonymous.
 */
final readonly class ServeCustomDomain
{
    private const SLUG = '[a-z0-9]+(?:-[a-z0-9]+)*';

    public function __construct(private BlogDomainRepository $domains) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if (
            $host === DomainServiceProvider::platformHost() ||
            in_array($host, ['localhost', '127.0.0.1'], true)
        ) {
            return $next($request);
        }

        $slug = $this->domains->activeBlogSlugForHost($host);

        if ($slug === null || ! $request->isMethod('GET')) {
            abort(404);
        }

        // Views expect $errors, normally shared by the session middleware.
        view()->share('errors', new ViewErrorBag);

        // Generated links (other posts, reporting, assets) point at the platform host,
        // where the full route set exists.
        URL::forceRootUrl((string) config('app.url'));

        $path = trim($request->path(), '/');

        return response(
            match (true) {
                $path === '' => app()->call(
                    [app(PublicBlogController::class), '__invoke'],
                    ['slug' => $slug],
                ),
                preg_match('#^p/('.self::SLUG.')$#', $path, $m) === 1 => app()->call(
                    [app(PublicPageController::class), '__invoke'],
                    ['slug' => $slug, 'page' => $m[1]],
                ),
                preg_match('#^('.self::SLUG.')$#', $path, $m) === 1 => app()->call(
                    [app(PublicPostController::class), '__invoke'],
                    ['slug' => $slug, 'post' => $m[1]],
                ),
                default => abort(404),
            },
        );
    }
}
