@php
    $content = $content ?? $section->props;
    $src = $mediaUrls[$content['mediaId'] ?? 0] ?? null;
    $alt = $content['alt'] ?? 'Template image';
    $position = $content['position'] ?? 'full';
    $alignment = match ($position) {
        'left' => 'mr-auto',
        'right' => 'ml-auto',
        default => 'mx-auto',
    };
    $width = $position === 'full' ? 'w-full' : 'w-2/3';
@endphp
<section>
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $alignment }} {{ $width }} block max-h-96 rounded-md object-cover" loading="lazy">
    @else
        <div class="flex h-48 items-center justify-center rounded-md border border-dashed text-sm opacity-60" style="border-color: var(--d-secondary);">
            Image placeholder
        </div>
    @endif
</section>
