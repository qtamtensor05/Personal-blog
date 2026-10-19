<section class="rounded-xl px-6 py-10" style="background: var(--d-secondary)">
    @if (!empty($content['mediaId']) && isset($mediaUrls[$content['mediaId']]))
        <img
            src="{{ $mediaUrls[$content['mediaId']] }}"
            alt=""
            class="mb-6 max-h-96 w-full rounded-lg object-cover"
        />
    @endif
    @if (!empty($content['eyebrow']))
        <p class="text-sm tracking-widest uppercase">{{ $content['eyebrow'] }}</p>
    @endif
    <h1
        class="mb-2 text-3xl font-bold"
        style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text)"
    >
        {{ $content['title'] ?? $blog->name }}
    </h1>
    <p class="opacity-80">{{
        $content['description'] ??
            $blog->description
    }}</p>
    @if (!empty($content['ctaLabel']) && !empty($content['ctaUrl']))
        <a
            href="{{ $content['ctaUrl'] }}"
            class="mt-4 inline-block rounded-md px-4 py-2 text-sm text-white"
            style="background: var(--d-primary)"
            >{{ $content['ctaLabel'] }}</a
        >
    @endif
</section>
