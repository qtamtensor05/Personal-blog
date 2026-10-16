@extends('layouts.app')

@section('title', 'Create Blog')

@section('content')
<div class="container-custom section-spacing max-w-2xl">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Create a New Blog</h1>
        <p class="text-secondary">Give your blog a name and slug. You can choose a template after.</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('blogs.store') }}" class="space-y-5">
            @csrf

            <x-input
                name="name"
                label="Blog Name"
                placeholder="My Awesome Blog"
                :error="$errors->first('name')"
                :value="old('name')"
                required
            />

            <x-input
                name="slug"
                label="Slug"
                placeholder="my-awesome-blog"
                hint="Lowercase letters, numbers and hyphens only. This will be your blog's URL."
                :error="$errors->first('slug')"
                :value="old('slug')"
                required
            />

            <x-textarea
                name="description"
                label="Description (optional)"
                placeholder="A few words about what your blog is about"
                rows="3"
                :error="$errors->first('description')">{{ old('description') }}</x-textarea>

            <div class="flex items-center gap-3 pt-4 border-t border-default">
                <button type="submit" class="btn-primary">Create Blog</button>
                <a href="{{ route('blogs.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
