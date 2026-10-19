<section class="rounded-lg border p-5" style="border-color: var(--d-secondary)">
    <h2 class="mb-3 font-semibold">
        {{
            $content['title'] ??
                'Bài viết mới'
        }}
    </h2>
    @foreach (array_slice($posts ?? [], 0, $content['limit'] ?? 4) as $post)
        <a
            class="block border-b py-2 text-sm"
            style="border-color: var(--d-secondary)"
            href="{{ route('public.post', [$blog->slug, $post->slug]) }}"
            >{{ $post->title }}</a
        >
    @endforeach
</section>
