<section class="p-5 rounded-md border" style="border-color: var(--d-primary);">
    <h2 class="text-lg font-semibold mb-2" style="font-family: var(--d-heading-font), sans-serif;">{{ $content['title'] ?? 'Stay in touch' }}</h2>
    @if(!empty($content['description']))<p class="mb-3 text-sm opacity-80">{{ $content['description'] }}</p>@endif
    <div class="flex gap-2">
        <span class="flex-1 px-3 py-2 rounded-md bg-white/80 text-sm opacity-60">you@example.com</span>
        <span class="px-4 py-2 rounded-md text-sm text-white" style="background: var(--d-primary);">{{ $content['ctaLabel'] ?? 'Subscribe' }}</span>
    </div>
</section>
