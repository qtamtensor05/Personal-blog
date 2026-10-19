{{-- $posts is passed on the public page; the design editor preview shows placeholders. --}}
<section>
    <h2 class="mb-3 text-xl font-semibold" style="font-family: var(--d-heading-font), sans-serif">
        {{
            $content['title'] ??
                'Recent posts'
        }}
    </h2>
    @php ($shownPosts = array_slice( array_filter( $posts ?? [], fn($post) => empty($content['categoryId']) || in_array((int) $content['categoryId'], $post->categoryIds, true) ), 0, $content['limit'] ?? 6 ))
    @if (isset($posts))
        @if (count($shownPosts) === 0)
            <p class="opacity-70">No posts published yet.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($shownPosts as $post)
                    <article
                        class="rounded-md border bg-white/70 p-4"
                        style="border-color: var(--d-secondary)"
                    >
                        <h3
                            class="font-semibold"
                            style="font-family: var(--d-heading-font), sans-serif"
                        >
                            <a
                                href="{{ route('public.post', [$blog->slug, $post->slug]) }}"
                                style="color: var(--d-text)"
                                >{{ $post->title }}</a
                            >
                        </h3>
                        <p class="text-sm opacity-70">{{
                            $post->excerpt ??
                                $post->content->plainText(160)
                        }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (['First post', 'Second post'] as $title)
                <article
                    class="rounded-md border bg-white/70 p-4"
                    style="border-color: var(--d-secondary)"
                >
                    <span class="text-xs font-medium" style="color: var(--d-accent)">Category</span>
                    <h3
                        class="mt-1 font-semibold"
                        style="font-family: var(--d-heading-font), sans-serif"
                    >
                        {{ $title }}
                    </h3>
                    <p class="text-sm opacity-70">Post excerpt preview.</p>
                </article>
            @endforeach
        </div>
    @endif
</section>
