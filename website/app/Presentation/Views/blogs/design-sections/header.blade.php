<div class="flex items-center justify-between gap-4 border-b pb-4" style="border-color: var(--d-secondary);">
    <div class="flex items-center gap-3">
        @if(!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
            <img src="{{ $mediaUrls[$content['mediaId']] }}" alt="" class="h-10 w-10 rounded-md object-cover">
        @endif
        <div>
            <a href="{{ route('public.blog', $blog->slug) }}" class="font-semibold text-lg">{{ $content['title'] ?? $blog->name }}</a>
            @if(!empty($content['description']))<p class="text-sm opacity-70">{{ $content['description'] }}</p>@endif
        </div>
    </div>
    @if(!empty($content['ctaLabel']) && !empty($content['ctaUrl']))
        <a href="{{ $content['ctaUrl'] }}" class="rounded-md px-4 py-2 text-sm text-white" style="background: var(--d-primary);">{{ $content['ctaLabel'] }}</a>
    @endif
</div>
