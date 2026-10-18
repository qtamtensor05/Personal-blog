<section class="py-10 rounded-xl px-6" style="background: var(--d-secondary);">
    @if(!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        <img src="{{ $mediaUrls[$content['mediaId']] }}" alt="" class="mb-6 w-full max-h-96 object-cover rounded-lg">
    @endif
    @if(!empty($content['eyebrow']))<p class="text-sm uppercase tracking-widest">{{ $content['eyebrow'] }}</p>@endif
    <h1 class="text-3xl font-bold mb-2" style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text);">{{ $content['title'] ?? $blog->name }}</h1>
    <p class="opacity-80">{{ $content['description'] ?? $blog->description }}</p>
    @if(!empty($content['ctaLabel']) && !empty($content['ctaUrl']))
        <a href="{{ $content['ctaUrl'] }}" class="inline-block mt-4 px-4 py-2 rounded-md text-sm text-white" style="background: var(--d-primary);">{{ $content['ctaLabel'] }}</a>
    @endif
</section>
