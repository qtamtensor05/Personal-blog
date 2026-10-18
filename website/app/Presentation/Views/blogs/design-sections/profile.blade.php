<section class="rounded-lg border p-5" style="border-color: var(--d-secondary);">
    @if(!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        <img src="{{ $mediaUrls[$content['mediaId']] }}" alt="" class="w-16 h-16 rounded-full object-cover mb-3">
    @endif
    <h2 class="font-semibold">{{ $content['title'] ?? $blog->name }}</h2>
    @if(!empty($content['description']))<p class="text-sm opacity-75 mt-2">{{ $content['description'] }}</p>@endif
</section>
