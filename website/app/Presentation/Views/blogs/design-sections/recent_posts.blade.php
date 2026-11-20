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
                        class="overflow-hidden rounded-md border bg-white/70"
                        style="border-color: var(--d-secondary)"
                    >
                        <a href="{{ route('public.post', [$blog->slug, $post->slug]) }}" class="block h-full p-4 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent-primary">
                            <img src="{{ $post->coverMediaId && isset($mediaUrls[$post->coverMediaId]) ? $mediaUrls[$post->coverMediaId] : asset('images/illustrations/post-cover.svg') }}" alt="" class="aspect-video w-full object-cover" loading="lazy" />
                        <h3
                            class="mt-3 font-semibold"
                            style="font-family: var(--d-heading-font), sans-serif"
                        >
                            <span style="color: var(--d-text)">{{ $post->title }}</span>
                        </h3>
                        <p class="text-sm opacity-70">{{
                            $post->excerpt ??
                                $post->content->plainText(160)
                        }}</p>
                        @if (isset($engagement[$post->id]))
                            <div class="mt-3 flex items-center gap-4 text-xs opacity-70">
                                <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.likes_count') }}: {{ $engagement[$post->id]->likes }}"><i class="fa-regular fa-heart" aria-hidden="true"></i>{{ \App\Presentation\Formatting\CompactCount::format($engagement[$post->id]->likes) }}</span>
                                <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.views_count') }}: {{ $engagement[$post->id]->views }}"><i class="fa-regular fa-eye" aria-hidden="true"></i>{{ $engagement[$post->id]->views }}</span>
                            </div>
                        @endif
                        </a>
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
