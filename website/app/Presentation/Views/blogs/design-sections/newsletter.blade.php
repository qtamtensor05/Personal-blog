<section class="rounded-md border p-5" style="border-color: var(--d-primary)">
    <h2 class="mb-2 text-lg font-semibold" style="font-family: var(--d-heading-font), sans-serif">
        {{
            $content['title'] ??
                'Stay in touch'
        }}
    </h2>
    @if (!empty($content['description']))
        <p class="mb-3 text-sm opacity-80">{{ $content['description'] }}</p>
    @endif
    <div class="flex gap-2">
        <span class="flex-1 rounded-md bg-white/80 px-3 py-2 text-sm opacity-60"
            >you@example.com</span
        >
        <span
            class="rounded-md px-4 py-2 text-sm text-white"
            style="background: var(--d-primary)"
            >{{
                $content['ctaLabel'] ??
                    'Subscribe'
            }}</span
        >
    </div>
</section>
