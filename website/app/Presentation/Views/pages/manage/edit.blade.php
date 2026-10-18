@extends('layouts.app')

@section('title', 'Edit — '.$editable->title)

@section('content')
{{-- Reuses the post editor script; pages have no excerpt field. --}}
<div class="container-custom section-spacing max-w-6xl" data-post-editor>
    <script type="application/json" data-editor-config>@json([
        'blogId' => $blog->id,
        'content' => $editable->content->toArray(),
        'mediaUrls' => (object) $mediaUrls,
        'draftUrl' => route('blogs.pages.draft', [$blog->id, $page->id]),
        'mediaUrl' => route('media.store'),
    ])</script>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('blogs.pages.index', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← Pages</a>
            <div class="mt-2 flex items-center gap-2">
                <x-badge :variant="$page->status->value === 'PUBLISHED' ? 'primary' : 'default'">{{ $page->status->value }}</x-badge>
                <span class="text-sm text-secondary" data-save-status aria-live="polite">
                    {{ $page->draft !== null && $page->publishedAt !== null ? 'Unpublished edits' : 'All changes saved' }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($page->status->value === 'PUBLISHED')
                <a href="{{ route('public.page', [$blog->slug, $page->slug]) }}" target="_blank" rel="noopener" class="btn-ghost text-sm">View</a>
                <form method="POST" action="{{ route('blogs.pages.status', [$blog->id, $page->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="DRAFT">
                    <button type="submit" class="btn-secondary text-sm">Unpublish</button>
                </form>
            @endif
            <form method="POST" action="{{ route('blogs.pages.publish', [$blog->id, $page->id]) }}" data-publish-form>
                @csrf
                <button type="submit" class="btn-primary">
                    {{ $page->publishedAt === null ? 'Publish' : 'Publish changes' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-8">
        <div class="card space-y-4">
            <label for="title" class="sr-only">Title</label>
            <input id="title" name="title" value="{{ $editable->title }}" maxlength="160" required
                   class="w-full text-3xl font-semibold text-primary bg-transparent focus:outline-none" placeholder="Page title">

            <div class="flex flex-wrap gap-1 border-y border-default py-2" role="toolbar" aria-label="Formatting">
                @foreach([
                    'bold' => 'Bold', 'italic' => 'Italic', 'h2' => 'H2', 'h3' => 'H3',
                    'bulletList' => '• List', 'orderedList' => '1. List', 'blockquote' => 'Quote',
                    'codeBlock' => 'Code', 'link' => 'Link', 'image' => 'Image',
                ] as $command => $label)
                    <button type="button" class="btn-ghost text-sm px-3 py-1" data-command="{{ $command }}">{{ $label }}</button>
                @endforeach
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-image-input>
            </div>

            <div data-editor></div>
        </div>

        <form method="POST" action="{{ route('blogs.pages.meta', [$blog->id, $page->id]) }}" class="card space-y-4 self-start">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold text-primary">Details</h2>
            <x-input name="slug" label="Slug" :value="$page->slug" required maxlength="160" />
            <p class="text-xs text-secondary">Page key: <span class="font-mono">{{ $page->pageKey }}</span> (fixed)</p>
            <x-input name="seo_title" label="SEO title" :value="$page->seoTitle" maxlength="255" />
            <x-textarea name="seo_description" label="SEO description" rows="2" maxlength="500">{{ $page->seoDescription }}</x-textarea>
            <button type="submit" class="btn-secondary w-full justify-center">Save details</button>
        </form>
    </div>

    <form method="POST" action="{{ route('blogs.pages.destroy', [$blog->id, $page->id]) }}" class="mt-8"
          data-modal-confirm-form data-confirm-title="Delete page" data-confirm-message="Delete this page?">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-500 hover:underline">Delete page</button>
    </form>
</div>
@endsection

@push('scripts')
    @vite('app/Presentation/Js/post-editor.js')
@endpush
