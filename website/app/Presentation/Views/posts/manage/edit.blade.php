@extends('layouts.app')

@section('title', 'Edit — '.$editable->title)

@section('content')
@php
    $editorConfig = [
        'blogId' => $blog->id,
        'content' => $editable->content->toArray(),
        'mediaUrls' => (object) $mediaUrls,
        'draftUrl' => route('blogs.posts.draft', [$blog->id, $post->id]),
        'mediaUrl' => route('media.store'),
    ];
@endphp
<div class="container-custom section-spacing max-w-6xl" data-post-editor>
    <script type="application/json" data-editor-config>{{ Illuminate\Support\Js::from($editorConfig) }}</script>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('blogs.posts.index', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← Bài viết</a>
            <div class="mt-2 flex items-center gap-2">
                <x-badge :variant="$post->status->value === 'PUBLISHED' ? 'primary' : 'default'">{{ $post->status->value }}</x-badge>
                <span class="text-sm text-secondary" data-save-status aria-live="polite">
                    {{ $post->draft !== null && $post->publishedAt !== null ? 'Unpublished edits' : 'All changes saved' }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($post->status->value === 'PUBLISHED')
                <a href="{{ route('public.post', [$blog->slug, $post->slug]) }}" target="_blank" rel="noopener" class="btn-ghost text-sm">View</a>
                <form method="POST" action="{{ route('blogs.posts.status', [$blog->id, $post->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="DRAFT">
                    <button type="submit" class="btn-secondary text-sm">Unpublish</button>
                </form>
            @endif
            <form method="POST" action="{{ route('blogs.posts.publish', [$blog->id, $post->id]) }}" data-publish-form>
                @csrf
                <button type="submit" class="btn-primary">
                    {{ $post->publishedAt === null ? 'Publish' : 'Publish changes' }}
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
        {{-- Writing area --}}
        <div class="card space-y-4">
            <label for="title" class="block text-sm font-medium text-secondary">Tên bài viết</label>
            <input id="title" name="title" value="{{ $editable->title }}" maxlength="160" required
                   class="w-full text-3xl font-semibold text-primary bg-transparent focus:outline-none" placeholder="Nhập tên bài viết">

            <label for="excerpt" class="block text-sm font-medium text-secondary">Mô tả ngắn</label>
            <textarea id="excerpt" name="excerpt" rows="2" maxlength="1000" placeholder="Mô tả ngắn hiển thị trong danh sách bài viết (không bắt buộc)"
                      class="textarea min-h-0">{{ $editable->excerpt }}</textarea>

            <div>
                <p class="mb-2 text-sm font-medium text-secondary">Nội dung bài viết</p>
                <div class="flex flex-wrap gap-1 border-y border-default py-2" role="toolbar" aria-label="Định dạng nội dung">
                @foreach([
                    'bold' => 'Đậm', 'italic' => 'Nghiêng', 'h2' => 'Tiêu đề H2', 'h3' => 'Tiêu đề H3',
                    'bulletList' => '• Danh sách', 'orderedList' => '1. Danh sách', 'blockquote' => 'Trích dẫn',
                    'codeBlock' => 'Mã', 'link' => 'Liên kết', 'image' => 'Thêm hình ảnh',
                ] as $command => $label)
                    <button type="button" class="btn-ghost text-sm px-3 py-1" data-command="{{ $command }}">{{ $label }}</button>
                @endforeach
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-image-input>
                </div>
            </div>

            <div data-editor></div>
        </div>

        {{-- Post details --}}
        <form method="POST" action="{{ route('blogs.posts.meta', [$blog->id, $post->id]) }}" class="card space-y-4 self-start">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold text-primary">Thông tin bài viết</h2>

            <x-input name="slug" label="Slug" :value="$post->slug" required maxlength="160" />

            @if(count($categories) > 0)
                <fieldset class="space-y-1">
                    <legend class="text-sm font-medium text-primary">Categories</legend>
                    @foreach($categories as $category)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $post->categoryIds, true))>
                            {{ $category->name }}
                        </label>
                    @endforeach
                </fieldset>
            @endif

            @if(count($tags) > 0)
                <fieldset class="space-y-1">
                    <legend class="text-sm font-medium text-primary">Tags</legend>
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        @foreach($tags as $tag)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $post->tagIds, true))>
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="space-y-1">
                <label for="cover_media_id" class="block text-sm font-medium text-primary">Ảnh đại diện bài viết</label>
                <select id="cover_media_id" name="cover_media_id" class="input">
                    <option value="">Chưa chọn ảnh đại diện</option>
                    @foreach($mediaAssets as $asset)
                        <option value="{{ $asset->id }}" @selected($post->coverMediaId === $asset->id)>#{{ $asset->id }} - {{ $asset->altText ?: 'Hình ảnh' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-secondary">Dùng nút “Thêm hình ảnh” trong nội dung để tải ảnh mới, sau đó lưu hoặc tải lại trang để chọn làm ảnh đại diện.</p>
            </div>
            <div class="space-y-1">
                <label for="og_media_id" class="block text-sm font-medium text-primary">Ảnh chia sẻ mạng xã hội</label>
                <select id="og_media_id" name="og_media_id" class="input">
                    <option value="">Dùng ảnh đại diện</option>
                    @foreach($mediaAssets as $asset)
                        <option value="{{ $asset->id }}" @selected($post->ogMediaId === $asset->id)>#{{ $asset->id }} - {{ $asset->altText ?: 'Hình ảnh' }}</option>
                    @endforeach
                </select>
            </div>
            <x-input name="seo_title" label="SEO title" :value="$post->seoTitle" maxlength="255" />
            <x-textarea name="seo_description" label="SEO description" rows="2" maxlength="500">{{ $post->seoDescription }}</x-textarea>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" @checked($post->isFeatured)>
                Featured post
            </label>

            <button type="submit" class="btn-secondary w-full justify-center">Save details</button>
        </form>
    </div>

    <form method="POST" action="{{ route('blogs.posts.destroy', [$blog->id, $post->id]) }}" class="mt-8"
          data-modal-confirm-form data-confirm-title="Delete post" data-confirm-message="Delete this post?">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-500 hover:underline">Delete post</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const title = document.getElementById('title');
    const slug = document.querySelector('[name=slug]');
    if (!title || !slug) return;
    let edited = slug.value !== title.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    title.addEventListener('input', () => { if (!edited) slug.value = slugify(title.value); });
    slug.addEventListener('input', () => { edited = true; });
})();
</script>
    @vite('app/Presentation/Js/post-editor.js')
@endpush
