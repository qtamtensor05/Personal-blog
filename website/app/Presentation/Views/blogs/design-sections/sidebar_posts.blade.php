<section class="rounded-lg border p-5" style="border-color: var(--d-secondary);">
    <h2 class="font-semibold mb-3">{{ $content['title'] ?? 'Bài viết mới' }}</h2>
    @foreach(array_slice($posts ?? [], 0, $content['limit'] ?? 4) as $post)
        <a class="block text-sm py-2 border-b" style="border-color: var(--d-secondary);" href="{{ route('public.post', [$blog->slug, $post->slug]) }}">{{ $post->title }}</a>
    @endforeach
</section>
