<?php

namespace App\Http\Controllers;

use App\Modules\Blogs\Actions\CreateBlogAction;
use App\Modules\Templates\Queries\TemplateLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class BlogAdminController extends Controller
{
    public function index(Request $request): View
    {
        $blogs = DB::table('blogs')
            ->where('owner_user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $templates = app(TemplateLibrary::class)->query()->orderBy('name')->get();

        return view('blog.dashboard', compact('blogs', 'templates'));
    }

    public function store(Request $request, CreateBlogAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'alpha_dash', 'max:160', 'unique:blogs,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'template_id' => ['required', 'integer'],
        ]);
        $id = $action->execute($request->user(), $data);

        $request->session()->forget('selected_template');

        return redirect("/blog-admin/studio?blog={$id}")->with('status', 'Đã tạo blog. Giao diện đang là bản nháp.');
    }

    public function show(Request $request, int $blog): View
    {
        $record = DB::table('blogs')
            ->where(['id' => $blog, 'owner_user_id' => $request->user()->id])
            ->whereNull('deleted_at')
            ->first();
        abort_unless($record, 404);
        $posts = DB::table('posts')
            ->where('blog_id', $blog)
            ->whereNull('deleted_at')
            ->latest()
            ->paginate(15);

        return view('blog.show', ['blog' => $record, 'posts' => $posts]);
    }
}
