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

    <section class="mb-8">
        <div class="mb-4">
            <p class="text-sm font-semibold uppercase tracking-wider text-accent-primary">Quản lý website</p>
            <h2 class="mt-1 text-2xl font-semibold text-primary">Trang chủ và bố cục</h2>
            <p class="mt-1 text-sm text-secondary">Chỉnh nội dung trong các vị trí cố định của template đang sử dụng.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                'header' => ['Header', 'Logo, tên blog, menu và CTA đầu trang.'],
                'body' => ['Body / Trang chủ', 'Banner, hero, nội dung, bài viết mới và chuyên mục.'],
                'sidebar' => ['Sidebar', 'Thông tin blogger, bài mới và newsletter.'],
                'footer' => ['Footer', 'Mô tả, liên kết, mạng xã hội và copyright.'],
            ] as $region => [$label, $description])
                <a href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => $region]) }}"
                   class="card group block transition hover:-translate-y-0.5 hover:border-accent-primary">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-md bg-surface-secondary text-accent-primary">
                        <span class="font-semibold">{{ strtoupper(substr($label, 0, 1)) }}</span>
                    </div>
                    <h3 class="font-semibold text-primary group-hover:text-accent-primary">{{ $label }}</h3>
                    <p class="mt-2 text-sm text-secondary">{{ $description }}</p>
                    <span class="mt-4 inline-block text-sm font-medium text-accent-primary">Quản lý nội dung →</span>
                </a>
            @endforeach
            <article class="card transition hover:-translate-y-0.5 hover:border-accent-primary">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-md bg-surface-secondary text-accent-primary">
                    <span class="font-semibold">B</span>
                </div>
                <h3 class="font-semibold text-primary">Bài viết</h3>
                <p class="mt-2 text-sm text-secondary">Tạo bài viết, thêm ảnh, chọn chuyên mục và xuất bản nội dung.</p>
                <div class="mt-4 flex flex-col items-start gap-2 text-sm font-medium">
                    <a href="{{ route('blogs.posts.index', $blog->id) }}" class="text-accent-primary hover:underline">Thêm / quản lý bài viết →</a>
                    <a href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => 'body']).'#slot-latest-posts' }}" class="text-accent-primary hover:underline">Cấu hình hiển thị bài viết →</a>
                </div>
            </article>
            <a href="{{ route('blogs.media.index', $blog->id) }}"
               class="card group block transition hover:-translate-y-0.5 hover:border-accent-primary">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-md bg-surface-secondary text-accent-primary">
                    <span class="font-semibold">M</span>
                </div>
                <h3 class="font-semibold text-primary group-hover:text-accent-primary">Media</h3>
                <p class="mt-2 text-sm text-secondary">Tải và quản lý hình ảnh, video dùng trong template và bài viết.</p>
                <span class="mt-4 inline-block text-sm font-medium text-accent-primary">Mở thư viện Media →</span>
            </a>
        </div>
    </section>

    <div class="mb-8">
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
        <a href="{{ route('blogs.taxonomy', $blog->id) }}" class="btn-secondary">Categories &amp; Tags</a>
        <a href="{{ route('blogs.pages.index', $blog->id) }}" class="btn-secondary">Pages</a>
        <a href="{{ route('blogs.domains.index', $blog->id) }}" class="btn-secondary">Domains</a>
        <form method="POST" action="{{ route('blogs.destroy', $blog->id) }}" class="inline" data-modal-confirm-form data-confirm-title="Delete blog" data-confirm-message="Delete this blog? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:underline">Delete Blog</button>
        </form>
    </div>
</div>
@endsection
