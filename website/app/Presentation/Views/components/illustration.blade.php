@props (['name', 'alt' => '', 'ratio' => 'aspect-[4/3]',])

@php
    $path = "images/illustrations/{$name}.svg";
    $exists = file_exists(public_path($path));
@endphp

@if ($exists || app()->isLocal())
    <div
        {{
            $attributes->class([
                'relative',
                $ratio,
            ])
        }}
    >
        @if ($exists)
            <img
                src="{{ asset($path) }}"
                alt="{{ $alt }}"
                @if ($alt === '') aria-hidden="true" @endif
                class="h-full w-full object-contain"
                loading="lazy"
                decoding="async"
            />
        @else
            <div
                class="border-accent-primary/30 bg-accent-primary/5 flex h-full w-full flex-col items-center justify-center gap-2 rounded-3xl border-2 border-dashed p-4 text-center"
            >
                <i class="fa-solid fa-sparkles text-accent-primary/60 h-8 w-8" aria-hidden="true"></i>
                <p class="text-secondary text-xs font-medium">Add an SVG here</p>
                <code class="text-secondary/80 text-[11px] break-all">public/{{ $path }}</code>
            </div>
        @endif
    </div>
@endif
