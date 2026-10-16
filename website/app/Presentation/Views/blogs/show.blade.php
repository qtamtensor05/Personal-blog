@extends('layouts.app')

@section('title', $blog->name)

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-hero mb-2">{{ $blog->name }}</h1>
                <p class="text-sm text-secondary font-mono">{{ $blog->slug }}</p>
            </div>
            <x-badge :variant="$blog->status->value === 'PUBLISHED' ? 'primary' : 'default'">
                {{ $blog->status->value }}
            </x-badge>
        </div>
        @if($blog->description)
            <p class="text-secondary">{{ $blog->description }}</p>
        @endif
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="card">
            <h2 class="text-lg font-semibold text-primary mb-4">Design</h2>
            <p class="text-sm text-secondary mb-4">Customize your blog's look and feel.</p>
            <a href="{{ route('blogs.design.edit', $blog->id) }}" class="btn-primary text-sm">
                Customize Design
            </a>
        </div>

        <div class="card">
            <h2 class="text-lg font-semibold text-primary mb-4">Posts</h2>
            <p class="text-sm text-secondary mb-4">Write and publish blog posts.</p>
            <a href="#" class="btn-primary text-sm" onclick="alert('Post management is upcoming'); return false;">
                Manage Posts
            </a>
        </div>

        <div class="card">
            <h2 class="text-lg font-semibold text-primary mb-4">Settings</h2>
            <p class="text-sm text-secondary mb-4">Configure blog settings and preferences.</p>
            <a href="{{ route('blogs.settings.edit', $blog->id) }}" class="btn-primary text-sm">
                Open Settings
            </a>
        </div>

        <div class="card">
            <h2 class="text-lg font-semibold text-primary mb-4">View Public Site</h2>
            <p class="text-sm text-secondary mb-4">See how your blog looks to visitors.</p>
            <a href="{{ route('public.blog', $blog->slug) }}" target="_blank" rel="noopener" class="btn-secondary text-sm">
                Preview Blog
            </a>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-6 border-t border-default">
        <a href="{{ route('blogs.edit', $blog->id) }}" class="btn-secondary">Edit Blog Info</a>
        <form method="POST" action="{{ route('blogs.destroy', $blog->id) }}" class="inline" onsubmit="return confirm('Delete this blog? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:underline">Delete Blog</button>
        </form>
    </div>
</div>
@endsection
