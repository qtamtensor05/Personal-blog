@extends ('layouts.app')

@section ('title', 'Posts — ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-5xl">
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← {{ $blog->name }}</a
            >
            <h1 class="text-hero mt-2">Bài viết</h1>
            <p class="text-secondary">Tạo bài nháp rồi nhập tiêu đề, nội dung và hình ảnh trong trình soạn thảo.</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="card mb-8">
            <h2 class="text-primary text-lg font-semibold">Viết bài mới</h2>
            <p class="text-secondary mt-2 text-sm">Bài viết mới được tạo ở trạng thái nháp. Đường dẫn sẽ tự sinh từ tiêu đề và bạn vẫn có thể sửa sau.</p>
            <form method="POST" action="{{ route('blogs.posts.store', $blog->id) }}" class="mt-4">
                @csrf
                <input type="hidden" name="title" value="Bài viết chưa có tiêu đề" />
                <button type="submit" class="btn-primary">Mở trình soạn thảo</button>
            </form>
        </section>

        @if (count($posts) === 0)
            <x-empty-state message="No posts yet">Create your first post above.</x-empty-state>
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Title</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Status</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Updated</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-default divide-y">
                        @foreach ($posts as $post)
                            <tr>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}"
                                        class="text-primary hover:text-accent-primary font-medium"
                                    >
                                        {{ $post->editable()->title }}
                                    </a>
                                    <div class="text-secondary font-mono text-xs">
                                        {{ $post->slug }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge
                                        :variant="$post->status->value === 'PUBLISHED' ? 'primary' : 'default'"
                                        >{{ $post->status->value }}</x-badge
                                    >
                                    @if ($post->draft !== null && $post->publishedAt !== null)
                                        <x-badge variant="highlight">unpublished edits</x-badge>
                                    @endif
                                </td>
                                <td class="text-secondary px-4 py-3">
                                    {{
                                        \Carbon\Carbon::parse(
                                            $post->updatedAt,
                                        )->diffForHumans()
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('blogs.posts.edit', [$blog->id, $post->id]) }}"
                                        class="text-accent-primary hover:underline"
                                        >Edit</a
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
