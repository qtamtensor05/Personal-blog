<section>
    <h2 class="text-2xl font-semibold mb-4">{{ $content['title'] ?? 'Gallery' }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach($content['mediaIds'] ?? [] as $mediaId)
            @if(isset($mediaUrls[$mediaId]))<img src="{{ $mediaUrls[$mediaId] }}" alt="" class="rounded-lg aspect-square object-cover">@endif
        @endforeach
    </div>
</section>
