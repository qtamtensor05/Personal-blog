@php ($brandImage = ($logoUrl ?? null) ?: ($mediaUrls[$content['mediaId'] ?? 0] ?? null))
@if ($brandImage)
    <img src="{{ $brandImage }}" alt="" class="{{ $imageClass }}" />
@elseif ($showFallback ?? false)
    <span class="le-brand-mark" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
            <path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1Z" />
            <path d="M12 5v15" />
        </svg>
    </span>
@endif
