@extends('layouts.app')

@section('title', 'My Blogs')

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-hero">My Blogs</h1>
            <a href="{{ route('blogs.create') }}" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Blog
            </a>
        </div>
        <p class="text-secondary">Manage your blogs and choose templates to customize.</p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if(count($blogs) === 0)
        <x-empty-state message="No blogs yet" action="{{ route('blogs.create') }}" actionLabel="Create your first blog">
            Start by creating a blog, then choose a template and customize its design.
        </x-empty-state>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($blogs as $blog)
                <div class="card group">
                    <div class="mb-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-semibold text-primary group-hover:text-accent-primary transition-colors">
                                <a href="{{ route('blogs.show', $blog->id) }}">{{ $blog->name }}</a>
                            </h3>
                            <x-badge :variant="$blog->status->value === 'PUBLISHED' ? 'primary' : 'default'">
                                {{ $blog->status->value }}
                            </x-badge>
                        </div>
                        @if($blog->description)
                            <p class="text-sm text-secondary">{{ Str::limit($blog->description, 100) }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-default">
                        <a href="{{ route('blogs.edit', $blog->id) }}" class="text-sm text-accent-primary hover:underline">
                            Edit
                        </a>
                        <span class="text-xs text-secondary font-mono">{{ $blog->slug }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
