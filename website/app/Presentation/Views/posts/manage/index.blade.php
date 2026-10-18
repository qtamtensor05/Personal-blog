@extends('layouts.app')

@section('title', 'Posts — '.$blog->name)

@section('content')
<div class="container-custom section-spacing max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
        <h1 class="text-hero mt-2">Posts</h1>
        <p class="text-secondary">Write in the editor; changes are saved as a draft until you publish.</p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <section class="card mb-8">
        <h2 class="text-lg font-semibold text-primary mb-4">New post</h2>
        <form method="POST" action="{{ route('blogs.posts.store', $blog->id) }}" class="grid sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
            @csrf
            <x-input name="title" label="Title" required maxlength="160" />
            <x-input name="slug" label="Slug" required maxlength="160" hint="Generated from the title; edit if needed." />
            <button type="submit" class="btn-primary">Create &amp; write</button>
        </form>
    </section>

    @if(count($posts) === 0)
        <x-empty-state message="No posts yet">Create your first post above.</x-empty-state>
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
                    @foreach($posts as $post)
                        <tr>
                            <td class="py-3 px-4">
                                <a href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}" class="font-medium text-primary hover:text-accent-primary">
                                    {{ $post->editable()->title }}
                                </a>
                                <div class="text-xs text-secondary font-mono">{{ $post->slug }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :variant="$post->status->value === 'PUBLISHED' ? 'primary' : 'default'">{{ $post->status->value }}</x-badge>
                                @if($post->draft !== null && $post->publishedAt !== null)
                                    <x-badge variant="highlight">unpublished edits</x-badge>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-secondary">{{ \Carbon\Carbon::parse($post->updatedAt)->diffForHumans() }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}" class="text-accent-primary hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const title = document.querySelector('[name=title]');
    const slug = document.querySelector('[name=slug]');
    if (!title || !slug) return;
    let edited = false;
    const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    title.addEventListener('input', () => { if (!edited) slug.value = slugify(title.value); });
    slug.addEventListener('input', () => { edited = true; });
})();
</script>
@endpush
@endsection
