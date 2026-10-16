@extends('layouts.app')

@section('title', 'Categories & Tags — '.$blog->name)

@section('content')
<div class="container-custom section-spacing max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
        <h1 class="text-hero mt-2">Categories &amp; Tags</h1>
        <p class="text-secondary">Organize posts. Deleting a category or tag removes it from posts, not the posts themselves.</p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Categories --}}
        <section class="card space-y-5">
            <h2 class="text-lg font-semibold text-primary">Categories</h2>

            <form method="POST" action="{{ route('blogs.categories.store', $blog->id) }}" class="space-y-3">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-input name="name" label="Name" required />
                    <x-input name="slug" label="Slug" required hint="e.g. travel-notes" />
                </div>
                <div class="space-y-1">
                    <label for="parent_id" class="block text-sm font-medium text-primary">Parent</label>
                    <select name="parent_id" id="parent_id" class="input">
                        <option value="">None</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary text-sm">Add category</button>
            </form>

            @if(count($categories) === 0)
                <p class="text-sm text-secondary">No categories yet.</p>
            @else
                <ul class="divide-y divide-default border-t border-default">
                    @foreach($categories as $category)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <span class="font-medium text-primary">{{ $category->name }}</span>
                                <span class="text-xs text-secondary font-mono ml-1">{{ $category->slug }}</span>
                                @if(! $category->isActive)
                                    <x-badge class="ml-1">inactive</x-badge>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('blogs.categories.destroy', [$blog->id, $category->id]) }}"
                                  onsubmit="return confirm('Delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Tags --}}
        <section class="card space-y-5">
            <h2 class="text-lg font-semibold text-primary">Tags</h2>

            <form method="POST" action="{{ route('blogs.tags.store', $blog->id) }}" class="space-y-3">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-input name="name" label="Name" required />
                    <x-input name="slug" label="Slug" required />
                </div>
                <button type="submit" class="btn-primary text-sm">Add tag</button>
            </form>

            @if(count($tags) === 0)
                <p class="text-sm text-secondary">No tags yet.</p>
            @else
                <div class="flex flex-wrap gap-2 border-t border-default pt-4">
                    @foreach($tags as $tag)
                        <form method="POST" action="{{ route('blogs.tags.destroy', [$blog->id, $tag->id]) }}"
                              onsubmit="return confirm('Delete tag {{ $tag->name }}?')" class="inline-flex">
                            @csrf
                            @method('DELETE')
                            <span class="badge gap-2">
                                {{ $tag->name }}
                                <button type="submit" class="text-red-500" aria-label="Delete tag {{ $tag->name }}">×</button>
                            </span>
                        </form>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
