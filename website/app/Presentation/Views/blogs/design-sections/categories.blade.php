<section>
    <h2 class="mb-3 text-xl font-semibold" style="font-family: var(--d-heading-font), sans-serif">
        {{ $content['title'] ?? 'Categories' }}
    </h2>
    <div class="flex flex-wrap gap-2">
        @foreach (array_slice(
                array_filter(
                    $categories ?? [],
                    fn($category) => $category->isActive &&
                        (($content['categoryIds'] ?? []) === [] ||
                            in_array($category->id, $content['categoryIds'], true))
                ),
                0,
                $content['limit'] ?? 12
            )
            as $category)
            <span
                class="rounded-full px-3 py-1 text-sm"
                style="background: var(--d-accent); color: var(--d-text)"
                >{{ $category->name }}</span
            >
        @endforeach
    </div>
</section>
