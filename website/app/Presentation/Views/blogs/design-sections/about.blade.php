<section>
    <h2 class="mb-2 text-xl font-semibold" style="font-family: var(--d-heading-font), sans-serif">
        {{ $content['title'] ?? 'About' }}
    </h2>
    <p class="opacity-80">{{
        $content['description'] ??
            ($blog->description ??
                'A short introduction to the author and the blog.')
    }}</p>
</section>
