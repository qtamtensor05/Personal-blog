<section>
    <h2 class="text-xl font-semibold mb-3" style="font-family: var(--d-heading-font), sans-serif;">Recent posts</h2>
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach(['First post', 'Second post'] as $title)
            <article class="p-4 rounded-md bg-white/70 border" style="border-color: var(--d-secondary);">
                <span class="text-xs font-medium" style="color: var(--d-accent);">Category</span>
                <h3 class="font-semibold mt-1" style="font-family: var(--d-heading-font), sans-serif;">{{ $title }}</h3>
                <p class="text-sm opacity-70">Post excerpt preview.</p>
            </article>
        @endforeach
    </div>
</section>
