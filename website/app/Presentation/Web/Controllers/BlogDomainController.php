<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Domains\Contracts\BlogDomainRepository;
use App\Application\Domains\UseCases\ManageDomains\ManageDomainsService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BlogDomainController extends Controller
{
    public function index(
        int $blog,
        Request $request,
        OwnedBlogGuard $guard,
        BlogDomainRepository $domains,
        ManageDomainsService $service,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $items = $domains->forBlog($blog);

        return view('blogs.domains', [
            'blog' => $record,
            'domains' => array_map(fn ($d) => ['domain' => $d, 'dns' => $service->instructions($d)], $items),
        ]);
    }

    public function store(int $blog, Request $request, ManageDomainsService $service): RedirectResponse
    {
        $data = $request->validate(['hostname' => ['required', 'string', 'max:253']]);

        return $this->run($blog, fn () => $service->add((int) $request->user()->id, $blog, $data['hostname']),
            'Domain added. Create the DNS record below, then verify.');
    }

    public function verify(int $blog, int $domain, Request $request, ManageDomainsService $service): RedirectResponse
    {
        try {
            $ok = $service->verify((int) $request->user()->id, $blog, $domain);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return $ok
            ? redirect()->route('blogs.domains.index', $blog)->with('status', 'Domain verified. You can now activate it.')
            : redirect()->route('blogs.domains.index', $blog)->withErrors([
                'general' => 'The TXT record was not found yet. DNS changes can take a while; try again later.',
            ]);
    }

    public function activate(int $blog, int $domain, Request $request, ManageDomainsService $service): RedirectResponse
    {
        return $this->run($blog, fn () => $service->activate((int) $request->user()->id, $blog, $domain), 'Domain activated.');
    }

    public function disable(int $blog, int $domain, Request $request, ManageDomainsService $service): RedirectResponse
    {
        return $this->run($blog, fn () => $service->disable((int) $request->user()->id, $blog, $domain), 'Domain disabled.');
    }

    public function destroy(int $blog, int $domain, Request $request, ManageDomainsService $service): RedirectResponse
    {
        return $this->run($blog, fn () => $service->remove((int) $request->user()->id, $blog, $domain), 'Domain removed.');
    }

    private function run(int $blog, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('blogs.domains.index', $blog)->with('status', $message);
    }
}
