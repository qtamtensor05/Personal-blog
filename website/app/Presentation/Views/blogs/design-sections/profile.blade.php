<section class="rounded-lg border p-5" style="border-color: var(--d-secondary)">
    @if (!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        <img
            src="{{ $mediaUrls[$content['mediaId']] }}"
            alt=""
            class="mb-3 h-16 w-16 rounded-full object-cover"
        />
    @endif
    <h2 class="font-semibold">{{ $content['title'] ?? $blog->name }}</h2>
    @if (!empty($content['description']))
        <p class="mt-2 text-sm opacity-75">{{ $content['description'] }}</p>
    @endif
</section>
