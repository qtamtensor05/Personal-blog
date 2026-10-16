{{-- $posts is passed on the public page; the design editor preview shows placeholders. --}}
<section>
    <h2 class="text-xl font-semibold mb-3" style="font-family: var(--d-heading-font), sans-serif;">Recent posts</h2>
    @if(isset($posts))
        @if(count($posts) === 0)
            <p class="opacity-70">No posts published yet.</p>
        @else
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach($posts as $post)
                    <article class="p-4 rounded-md bg-white/70 border" style="border-color: var(--d-secondary);">
                        <h3 class="font-semibold" style="font-family: var(--d-heading-font), sans-serif;">
                            <a href="{{ route('public.post', [$blog->slug, $post->slug]) }}" style="color: var(--d-text);">{{ $post->title }}</a>
                        </h3>
                        <p class="text-sm opacity-70">{{ $post->excerpt ?? $post->content->plainText(160) }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    @else
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach(['First post', 'Second post'] as $title)
                <article class="p-4 rounded-md bg-white/70 border" style="border-color: var(--d-secondary);">
                    <span class="text-xs font-medium" style="color: var(--d-accent);">Category</span>
                    <h3 class="font-semibold mt-1" style="font-family: var(--d-heading-font), sans-serif;">{{ $title }}</h3>
                    <p class="text-sm opacity-70">Post excerpt preview.</p>
                </article>
            @endforeach
        </div>
    @endif
</section>
