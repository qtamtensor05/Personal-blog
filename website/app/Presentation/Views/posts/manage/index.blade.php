@extends('layouts.app')

@section('title', __('post.posts').' - '.$blog->name)

@section('content')
    <div class="container-custom section-spacing max-w-5xl">
        <div class="mb-8">
            <a href="{{ route('blogs.show', $blog->id) }}" class="text-secondary hover:text-accent-primary text-sm"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>{{ $blog->name }}</a>
            <h1 class="text-hero mt-2">{{ __('post.posts') }}</h1>
            <p class="text-secondary">{{ __('post.posts_intro') }}</p>
        </div>

        @if (session('status')) <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert> @endif
        @if ($errors->any()) <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert> @endif

        <section class="card mb-8">
            <h2 class="text-primary text-lg font-semibold">{{ __('post.new') }}</h2>
            <p class="text-secondary mt-2 text-sm">{{ __('post.new_help') }}</p>
            <form method="POST" action="{{ route('blogs.posts.store', $blog->id) }}" class="mt-4">
                @csrf
                <input type="hidden" name="title" value="{{ __('post.untitled') }}">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>{{ __('post.open_editor') }}</button>
            </form>
        </section>

        @if (count($posts) === 0)
            <x-empty-state :message="__('post.empty_title')">{{ __('post.empty_action') }}</x-empty-state>
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b"><tr>
                        <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('post.title') }}</th>
                        <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('post.status') }}</th>
                        <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('post.updated') }}</th>
                        <th class="px-4 py-3"><span class="sr-only">{{ __('post.actions') }}</span></th>
                    </tr></thead>
                    <tbody class="divide-default divide-y">
                        @foreach ($posts as $post)
                            <tr>
                                <td class="px-4 py-3"><a href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}" class="text-primary hover:text-accent-primary font-medium">{{ $post->editable()->title }}</a></td>
                                <td class="text-secondary px-4 py-3">{{ __('post.status_'.$post->status->value) }}</td>
                                <td class="text-secondary px-4 py-3">{{ \Illuminate\Support\Carbon::parse($post->updatedAt)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}" class="text-accent-primary hover:underline">{{ __('blog.edit') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($deletedPosts) > 0)
            <section class="card mt-8">
                <h2 class="text-primary mb-4 text-lg font-semibold">{{ __('post.recently_deleted') }}</h2>
                <ul class="space-y-3">
                    @foreach ($deletedPosts as $deletedPost)
                        <li class="flex flex-wrap items-center justify-between gap-3 border-b border-default pb-3">
                            <span>{{ $deletedPost->title }}</span>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('blogs.posts.restore', [$blog->id, $deletedPost->id]) }}">@csrf<button class="btn-secondary text-sm"><i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>{{ __('post.restore') }}</button></form>
                                <form method="POST" action="{{ route('blogs.posts.force-destroy', [$blog->id, $deletedPost->id]) }}" data-modal-confirm-form data-confirm-title="{{ __('post.delete_permanently') }}" data-confirm-message="{{ __('post.delete_warning') }}">@csrf @method('DELETE')<button class="btn-secondary text-sm text-red-600"><i class="fa-solid fa-trash-can mr-1" aria-hidden="true"></i>{{ __('post.delete_forever') }}</button></form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
