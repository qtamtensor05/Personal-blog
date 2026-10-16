<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\Exceptions\BlogSlugTaken;
use App\Application\Blogs\UseCases\CreateBlog\CreateBlogUseCase;
use App\Application\Blogs\UseCases\DeleteBlog\DeleteBlogUseCase;
use App\Application\Blogs\UseCases\UpdateBlog\UpdateBlogUseCase;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Presentation\Web\Requests\CreateBlogRequest;
use App\Presentation\Web\Requests\UpdateBlogRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(Request $request, BlogRepository $blogs): View
    {
        return view('blogs.index', [
            'blogs' => $blogs->ownedBy((int) $request->user()->id),
        ]);
    }

    public function create(): View
    {
        return view('blogs.create');
    }

    public function store(CreateBlogRequest $request, CreateBlogUseCase $create): RedirectResponse
    {
        try {
            $id = $create->execute(
                ownerUserId: (int) $request->user()->id,
                name: $request->validated('name'),
                slug: $request->validated('slug'),
                description: $request->validated('description'),
            );
        } catch (BlogSlugTaken $exception) {
            return back()->withErrors(['slug' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('blogs.show', $id)
            ->with('status', 'Blog created successfully.');
    }

    public function show(int $blog, BlogRepository $blogs, Request $request): View
    {
        $record = $blogs->find($blog);

        // Workspace page: owner only. Public rendering is a separate route.
        if ($record === null || $record->ownerUserId !== (int) $request->user()->id) {
            abort(404);
        }

        return view('blogs.show', ['blog' => $record]);
    }

    public function edit(int $blog, BlogRepository $blogs, Request $request): View
    {
        $record = $blogs->find($blog);

        if ($record === null || $record->ownerUserId !== (int) $request->user()->id) {
            abort(404);
        }

        return view('blogs.edit', ['blog' => $record]);
    }

    public function update(
        int $blog,
        UpdateBlogRequest $request,
        UpdateBlogUseCase $update,
    ): RedirectResponse {
        try {
            $update->execute(
                actorId: (int) $request->user()->id,
                blogId: $blog,
                name: $request->validated('name'),
                slug: $request->validated('slug'),
                description: $request->validated('description'),
                status: BlogStatus::from($request->validated('status')),
                visibility: BlogVisibility::from($request->validated('visibility')),
                discoverable: (bool) $request->validated('discoverable'),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (BlogSlugTaken $exception) {
            return back()->withErrors(['slug' => $exception->getMessage()])->withInput();
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('blogs.show', $blog)
            ->with('status', 'Blog updated successfully.');
    }

    public function destroy(int $blog, DeleteBlogUseCase $delete, Request $request): RedirectResponse
    {
        try {
            $delete->execute((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()
            ->route('blogs.index')
            ->with('status', 'Blog deleted.');
    }
}
