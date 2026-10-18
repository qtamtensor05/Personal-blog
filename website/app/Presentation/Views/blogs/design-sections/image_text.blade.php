<section class="grid sm:grid-cols-2 gap-6 items-center">
    @if(!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))<img src="{{ $mediaUrls[$content['mediaId']] }}" alt="{{ $content['alt'] ?? '' }}" class="rounded-lg w-full">@endif
    <div><h2 class="text-2xl font-semibold">{{ $content['title'] ?? '' }}</h2><p class="mt-3 opacity-80">{{ $content['description'] ?? '' }}</p></div>
</section>
