<section>
    <h2 class="mb-4 text-2xl font-semibold">{{ $content['title'] ?? 'Gallery' }}</h2>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        @foreach ($content['mediaIds'] ?? [] as $mediaId)
            @if (isset($mediaUrls[$mediaId]))
                <img
                    src="{{ $mediaUrls[$mediaId] }}"
                    alt=""
                    class="aspect-square rounded-lg object-cover"
                />
            @endif
        @endforeach
    </div>
</section>
