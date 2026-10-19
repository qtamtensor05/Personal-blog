@extends ('layouts.app')

@section ('title', 'Edit — ' . $editable->title)

@php
    $editorConfig = [
        'blogId' => $blog->id,
        'content' => $editable->content->toArray(),
        'mediaUrls' => (object) $mediaUrls,
        'draftUrl' => route('blogs.pages.draft', [$blog->id, $page->id]),
        'mediaUrl' => route('media.store'),
    ];
@endphp

@section ('content')
    {{-- Reuses the post editor script; pages have no excerpt field. --}}
    <div class="container-custom section-spacing max-w-6xl" data-post-editor>
        <script type="application/json" data-editor-config>{{ Illuminate\Support\Js::from($editorConfig) }}</script>

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <a
                    href="{{ route('blogs.pages.index', $blog->id) }}"
                    class="text-secondary hover:text-accent-primary text-sm"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Pages
                </a>
                <div class="mt-2 flex items-center gap-2">
                    <x-badge
                        :variant="$page->status->value === 'PUBLISHED' ? 'primary' : 'default'"
                        >{{ $page->status->value }}</x-badge
                    >
                    <span class="text-secondary text-sm" data-save-status aria-live="polite">
                        {{
                            $page->draft !== null && $page->publishedAt !== null
                                ? __('pages.unpublished_edits')
                                : __('pages.all_saved')
                        }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if ($page->status->value === 'PUBLISHED')
                    <a
                        href="{{ route('public.page', [$blog->slug, $page->slug]) }}"
                        target="_blank"
                        rel="noopener"
                        class="btn-ghost text-sm"
                        >View</a
                    >
                    <form
                        method="POST"
                        action="{{ route('blogs.pages.status', [$blog->id, $page->id]) }}"
                    >
                        @csrf
                        @method ('PATCH')
                        <input type="hidden" name="status" value="DRAFT" />
                        <button type="submit" class="btn-secondary text-sm">{{ __('pages.unpublish') }}</button>
                    </form>
                @endif
                <form
                    method="POST"
                    action="{{ route('blogs.pages.publish', [$blog->id, $page->id]) }}"
                    data-publish-form
                >
                    @csrf
                    <button type="submit" class="btn-primary">
                        {{
                            $page->publishedAt === null
                                ? __('pages.publish')
                                : __('pages.publish_changes')
                        }}
                    </button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="card space-y-4">
                <label for="title" class="sr-only">{{ __('pages.title_label') }}</label>
                <input
                    id="title"
                    name="title"
                    value="{{ $editable->title }}"
                    maxlength="160"
                    required
                    class="text-primary w-full bg-transparent text-3xl font-semibold focus:outline-none"
                    placeholder="{{ __('pages.title_placeholder') }}"
                />

                <div
                    class="border-default flex flex-wrap gap-1 border-y py-2"
                    role="toolbar"
                    aria-label="{{ __('pages.formatting') }}"
                >
                    @foreach ([
                            'bold' => __('pages.bold'),
                            'italic' => __('pages.italic'),
                            'h2' => 'H2',
                            'h3' => 'H3',
                            'bulletList' => '• List',
                            'orderedList' => __('pages.ordered_list'),
                            'blockquote' => __('pages.quote'),
                            'codeBlock' => __('pages.code'),
                            'link' => __('pages.link'),
                            'image' => __('pages.image')
                        ]
                        as $command => $label)
                        <button
                            type="button"
                            class="btn-ghost px-3 py-1 text-sm"
                            data-command="{{ $command }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="hidden"
                        data-image-input
                    />
                </div>

                <div data-editor></div>
            </div>

            <form
                method="POST"
                action="{{ route('blogs.pages.meta', [$blog->id, $page->id]) }}"
                class="card space-y-4 self-start"
            >
                @csrf
                @method ('PUT')
                <h2 class="text-primary text-lg font-semibold">Details</h2>
                <x-input name="slug" label="Slug" :value="$page->slug" required maxlength="160" />
                <p class="text-secondary text-xs">Page key: <span class="font-mono">{{ $page->pageKey }}</span> (fixed)</p>
                <x-input
                    name="seo_title"
                    label="SEO title"
                    :value="$page->seoTitle"
                    maxlength="255"
                />
                <x-textarea
                    name="seo_description"
                    label="SEO description"
                    rows="2"
                    maxlength="500"
                    >{{ $page->seoDescription }}</x-textarea
                >
                <button type="submit" class="btn-secondary w-full justify-center">
                    Save details
                </button>
            </form>
        </div>

        <form
            method="POST"
            action="{{ route('blogs.pages.destroy', [$blog->id, $page->id]) }}"
            class="mt-8"
            data-modal-confirm-form
            data-confirm-title="Delete page"
            data-confirm-message="Delete this page?"
        >
            @csrf
            @method ('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:underline">Delete page</button>
        </form>
    </div>
@endsection

@push ('scripts')
    @vite ('app/Presentation/Js/post-editor.js')
@endpush
