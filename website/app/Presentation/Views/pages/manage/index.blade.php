@extends('layouts.app')

@section('title', 'Pages — '.$blog->name)

@section('content')
<div class="container-custom section-spacing max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
        <h1 class="text-hero mt-2">Pages</h1>
        <p class="text-secondary">Static pages such as About or Contact. Published pages are linked from your blog home.</p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <section class="card mb-8">
        <h2 class="text-lg font-semibold text-primary mb-4">New page</h2>
        <form method="POST" action="{{ route('blogs.pages.store', $blog->id) }}" class="grid sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
            @csrf
            <x-input name="title" label="Title" required maxlength="160" />
            <x-input name="slug" label="Slug" required maxlength="80" hint="e.g. about" />
            <button type="submit" class="btn-primary">Create &amp; write</button>
        </form>
    </section>

    @if(count($pages) === 0)
        <x-empty-state message="No pages yet">Create a page above, for example "About".</x-empty-state>
    @else
        <div class="card p-0 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-secondary border-b border-default">
                    <tr>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Title</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Updated</th>
                        <th class="py-3 px-4"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    @foreach($pages as $item)
                        <tr>
                            <td class="py-3 px-4">
                                <a href="{{ route('blogs.pages.edit', [$blog->id, $item->id]) }}" class="font-medium text-primary hover:text-accent-primary">
                                    {{ $item->editable()->title }}
                                </a>
                                <div class="text-xs text-secondary font-mono">{{ $item->slug }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :variant="$item->status->value === 'PUBLISHED' ? 'primary' : 'default'">{{ $item->status->value }}</x-badge>
                                @if($item->draft !== null && $item->publishedAt !== null)
                                    <x-badge variant="highlight">unpublished edits</x-badge>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-secondary">{{ \Carbon\Carbon::parse($item->updatedAt)->diffForHumans() }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('blogs.pages.edit', [$blog->id, $item->id]) }}" class="text-accent-primary hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
