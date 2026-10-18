<div class="flex items-center justify-between gap-4 border-b pb-4" style="border-color: var(--d-secondary);">
    <a href="{{ route('public.blog', $blog->slug) }}" class="font-semibold text-lg">{{ $blog->name }}</a>
    <span class="text-sm opacity-70">{{ $blog->description }}</span>
</div>
