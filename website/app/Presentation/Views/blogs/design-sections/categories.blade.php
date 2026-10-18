<section>
    <h2 class="text-xl font-semibold mb-3" style="font-family: var(--d-heading-font), sans-serif;">{{ $content['title'] ?? 'Categories' }}</h2>
    <div class="flex flex-wrap gap-2">
        @foreach(array_slice(array_filter($categories ?? [], fn ($category) => $category->isActive && (($content['categoryIds'] ?? []) === [] || in_array($category->id, $content['categoryIds'], true))), 0, $content['limit'] ?? 12) as $category)
            <span class="px-3 py-1 rounded-full text-sm" style="background: var(--d-accent); color: var(--d-text);">{{ $category->name }}</span>
        @endforeach
    </div>
</section>
