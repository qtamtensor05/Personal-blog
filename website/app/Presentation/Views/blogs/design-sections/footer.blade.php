<div class="border-t pt-4 text-sm opacity-70" style="border-color: var(--d-secondary)">
    {{
        $content['copyright'] ??
            '© ' . now()->year . ' ' . $blog->name
    }}
    @if (!empty($content['description']))
        <p class="mt-2">{{ $content['description'] }}</p>
    @endif
</div>
