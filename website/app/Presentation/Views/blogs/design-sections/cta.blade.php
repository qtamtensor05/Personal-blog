<section class="rounded-xl p-7" style="background: var(--d-primary); color: white">
    <h2 class="text-2xl font-semibold">
        {{
            $content['title'] ??
                'Stay connected'
        }}
    </h2>
    @if (!empty($content['description']))
        <p class="mt-2 opacity-90">{{ $content['description'] }}</p>
    @endif
    @if (!empty($content['ctaLabel']) && !empty($content['ctaUrl']))
        <a
            class="mt-4 inline-block underline"
            href="{{ $content['ctaUrl'] }}"
            >{{ $content['ctaLabel'] }}</a
        >
    @endif
</section>
