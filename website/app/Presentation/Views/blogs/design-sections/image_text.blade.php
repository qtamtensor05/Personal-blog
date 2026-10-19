<section class="grid items-center gap-6 sm:grid-cols-2">
    @if (!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        <img
            src="{{ $mediaUrls[$content['mediaId']] }}"
            alt="{{ $content['alt'] ?? '' }}"
            class="w-full rounded-lg"
        />
    @endif
    <div>
        <h2 class="text-2xl font-semibold">{{ $content['title'] ?? '' }}</h2>
        <p class="mt-3 opacity-80">{{ $content['description'] ?? '' }}</p>
    </div>
</section>
