<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Exceptions\BlogAlreadyExists;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\Exceptions\BlogSlugTaken;
use App\Application\Blogs\UseCases\CreateBlog\CreateBlogUseCase;
use App\Application\Blogs\UseCases\DeleteBlog\DeleteBlogUseCase;
use App\Application\Blogs\UseCases\UpdateBlog\UpdateBlogUseCase;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use App\Presentation\Web\Requests\CreateBlogRequest;
use App\Presentation\Web\Requests\UpdateBlogRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(Request $request, BlogRepository $blogs): View|RedirectResponse
    {
        $ownedBlogs = $blogs->ownedBy((int) $request->user()->id);

        return $ownedBlogs !== []
            ? redirect()->route('blogs.show', $ownedBlogs[0]->id)
            : view('blogs.index', ['blogs' => []]);
    }

    public function create(Request $request, BlogRepository $blogs): View|RedirectResponse
    {
        $ownedBlogs = $blogs->ownedBy((int) $request->user()->id);

        if ($ownedBlogs !== []) {
            return redirect()->route('blogs.show', $ownedBlogs[0]->id);
        }

        return view('blogs.create');
    }

    public function store(CreateBlogRequest $request, CreateBlogUseCase $create): RedirectResponse
    {
        try {
            $id = $create->execute(
                ownerUserId: (int) $request->user()->id,
                name: $request->validated('name'),
                slug: $request->validated('slug') ?: Str::slug($request->validated('name')),
                description: $request->validated('description'),
            );
        } catch (BlogAlreadyExists $exception) {
            return redirect()->route('blogs.show', $exception->blogId);
        } catch (BlogSlugTaken $exception) {
            return back()
                ->withErrors(['slug' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('blogs.show', $id)
            ->with(
                'status',
                'Blog created. Choose a template published by an admin to start your design.',
            );
    }

    public function show(
        int $blog,
        BlogRepository $blogs,
        Request $request,
        TaxonomyRepository $taxonomy,
    ): View {
        $record = $blogs->find($blog);

        // Workspace page: owner only. Public rendering is a separate route.
        if ($record === null || $record->ownerUserId !== (int) $request->user()->id) {
            abort(404);
        }

        return view('blogs.show', [
            'blog' => $record,
            'categories' => $taxonomy->categories($blog),
            'tags' => $taxonomy->tags($blog),
        ]);
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
                slug: $request->validated('slug') ?: Str::slug($request->validated('name')),
                description: $request->validated('description'),
                status: BlogStatus::from($request->validated('status')),
                visibility: BlogVisibility::from($request->validated('visibility') ?? 'PUBLIC'),
                discoverable: (bool) $request->validated('discoverable'),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (BlogSlugTaken $exception) {
            return back()
                ->withErrors(['slug' => $exception->getMessage()])
                ->withInput();
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('blogs.show', $blog)->with('status', 'Blog updated successfully.');
    }

    public function destroy(
        int $blog,
        DeleteBlogUseCase $delete,
        Request $request,
    ): RedirectResponse {
        try {
            $delete->execute((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()->route('blogs.index')->with('status', 'Blog deleted.');
    }
}
