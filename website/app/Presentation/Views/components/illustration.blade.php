@props([
    'name',
    'alt' => '',
    'ratio' => 'aspect-[4/3]',
])

@php
    $path = "images/illustrations/{$name}.svg";
    $exists = file_exists(public_path($path));
@endphp

@if ($exists || app()->isLocal())
    <div {{ $attributes->class(['relative', $ratio]) }}>
        @if ($exists)
            <img
                src="{{ asset($path) }}"
                alt="{{ $alt }}"
                @if ($alt === '') aria-hidden="true" @endif
                class="h-full w-full object-contain"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="flex h-full w-full flex-col items-center justify-center gap-2 rounded-3xl border-2 border-dashed border-accent-primary/30 bg-accent-primary/5 p-4 text-center">
                <svg class="h-8 w-8 text-accent-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3 16 5-5 4 4 3-3 6 6M4 4h16v16H4z"/>
                </svg>
                <p class="text-xs font-medium text-secondary">Add an SVG here</p>
                <code class="break-all text-[11px] text-secondary/80">public/{{ $path }}</code>
            </div>
        @endif
    </div>
@endif